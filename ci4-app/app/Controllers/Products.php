<?php

namespace App\Controllers;

use App\Models\ClientModel;
use App\Models\ProductClientPriceModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    private const PER_PAGE = 20;
    private const CLIENT_PRICE_PER_PAGE = 20;

    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [], string $basePath = 'products')
    {
        $redirect = redirect()
            ->to('/' . trim($basePath, '/'))
            ->withInput()
            ->with('error', $message)
            ->with('form_mode', $mode);

        if ($id !== null) {
            $redirect = $redirect->with('form_id', $id);
        }

        return $redirect;
    }

    public function index(): string
    {
        return $this->renderIndex('layout', 'products');
    }

    public function payablesIndex(): string
    {
        return $this->renderIndex('payables_layout', 'payables/products');
    }

    private function renderIndex(string $layout, string $basePath): string
    {
        $model = new ProductModel();
        $createdProductId = (int) ($this->request->getGet('created_product_id') ?? 0);
        $search = trim((string) ($this->request->getGet('q') ?? ''));

        if ($createdProductId > 0) {
            $model->where('id', $createdProductId);
        }

        if ($search !== '') {
            $model
                ->groupStart()
                ->like('product_id', $search)
                ->orLike('product_name', $search)
                ->groupEnd();
        }

        $page = max(1, (int) ($this->request->getGet('page_products') ?? $this->request->getGet('page') ?? 1));
        $products = $model->orderBy('product_name', 'asc')->paginate(self::PER_PAGE, 'products');

        return view('products/index', [
            'layout' => $layout,
            'basePath' => $basePath,
            'products' => $products,
            'pager' => $model->pager,
            'search' => $search,
            'rowOffset' => ($page - 1) * self::PER_PAGE,
        ]);
    }

    public function create()
    {
        return $this->createFor('products');
    }

    public function clientPrices(int $productId): string
    {
        $product = (new ProductModel())->find($productId);
        if (! $product) {
            throw PageNotFoundException::forPageNotFound();
        }

        $search = trim((string) ($this->request->getGet('q') ?? ''));
        $clientModel = new ClientModel();
        $clientModel
            ->select('clients.*, product_client_prices.price as assigned_price')
            ->join(
                'product_client_prices',
                'product_client_prices.client_id = clients.id AND product_client_prices.product_id = ' . (int) $productId,
                'left'
            );

        if ($search !== '') {
            $clientModel
                ->groupStart()
                ->like('clients.name', $search)
                ->orLike('clients.email', $search)
                ->orLike('clients.phone', $search)
                ->groupEnd();
        }

        $page = max(1, (int) ($this->request->getGet('page_client_prices') ?? $this->request->getGet('page') ?? 1));
        $clients = $clientModel
            ->orderBy('clients.name', 'asc')
            ->paginate(self::CLIENT_PRICE_PER_PAGE, 'client_prices');

        return view('products/client_prices', [
            'product' => $product,
            'clients' => $clients,
            'pager' => $clientModel->pager,
            'search' => $search,
            'rowOffset' => ($page - 1) * self::CLIENT_PRICE_PER_PAGE,
        ]);
    }

    public function saveClientPrice(int $productId, int $clientId)
    {
        $product = (new ProductModel())->find($productId);
        if (! $product) {
            throw PageNotFoundException::forPageNotFound();
        }

        $client = (new ClientModel())->find($clientId);
        if (! $client) {
            throw PageNotFoundException::forPageNotFound();
        }

        $price = trim((string) ($this->request->getPost('price') ?? ''));
        $priceModel = new ProductClientPriceModel();

        if ($price === '') {
            $priceModel
                ->where('product_id', $productId)
                ->where('client_id', $clientId)
                ->delete();

            return $this->redirectToClientPriceRow($productId, (string) $client['name'])
                ->with('success', 'Special price reset to product default.');
        }

        if (! is_numeric($price) || (float) $price < 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Price must be a number greater than or equal to 0.');
        }

        $existing = $priceModel
            ->where('product_id', $productId)
            ->where('client_id', $clientId)
            ->first();

        $data = [
            'product_id' => $productId,
            'client_id' => $clientId,
            'price' => $price,
        ];

        if ($existing) {
            $priceModel->update((int) $existing['id'], $data);
        } else {
            $priceModel->insert($data);
        }

        return $this->redirectToClientPriceRow($productId, (string) $client['name'])
            ->with('success', 'Special price saved.');
    }

    private function redirectToClientPriceRow(int $productId, string $clientName)
    {
        return redirect()->to('products/' . $productId . '/client-prices?' . http_build_query([
            'q' => $clientName,
        ]));
    }

    public function createPayables()
    {
        return $this->createFor('payables/products');
    }

    private function createFor(string $basePath)
    {
        $rules = [
            'product_id' => 'required|max_length[50]',
            'product_name' => 'required|max_length[150]',
            'unit_price' => 'required|numeric',
        ];

        $product = [
            'product_id' => trim((string) $this->request->getPost('product_id')),
            'product_name' => trim((string) $this->request->getPost('product_name')),
            'unit_price' => trim((string) $this->request->getPost('unit_price')),
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'create',
                null,
                $this->validator->getErrors(),
                $basePath
            );
        }

        $model = new ProductModel();
        $createdProductId = (int) $model->insert($product, true);

        return redirect()
            ->to('/' . trim($basePath, '/') . '?' . http_build_query([
                'q' => $product['product_name'],
                'created_product_id' => $createdProductId,
            ]))
            ->with('success', 'Product created.');
    }

    public function update(int $id)
    {
        return $this->updateFor($id, 'products');
    }

    public function updatePayables(int $id)
    {
        return $this->updateFor($id, 'payables/products');
    }

    private function updateFor(int $id, string $basePath)
    {
        $model = new ProductModel();
        $existing = $model->find($id);

        if (! $existing) {
            throw PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'product_id' => 'required|max_length[50]',
            'product_name' => 'required|max_length[150]',
            'unit_price' => 'required|numeric',
        ];

        $product = [
            'product_id' => trim((string) $this->request->getPost('product_id')),
            'product_name' => trim((string) $this->request->getPost('product_name')),
            'unit_price' => trim((string) $this->request->getPost('unit_price')),
        ];

        if (! $this->validate($rules)) {
            return $this->redirectWithFormState(
                'Please correct the highlighted fields.',
                'edit',
                $id,
                $this->validator->getErrors(),
                $basePath
            );
        }

        $model->update($id, $product);

        return redirect()->to('/' . trim($basePath, '/'))->with('success', 'Product updated.');
    }

    public function delete(int $id)
    {
        return $this->deleteFor($id, 'products');
    }

    public function deletePayables(int $id)
    {
        return $this->deleteFor($id, 'payables/products');
    }

    private function deleteFor(int $id, string $basePath)
    {
        $model = new ProductModel();
        $model->delete($id);

        return redirect()->to('/' . trim($basePath, '/'))->with('success', 'Product deleted.');
    }
}
