<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    private const PER_PAGE = 20;

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
