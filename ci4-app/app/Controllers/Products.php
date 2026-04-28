<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    private const PER_PAGE = 20;

    private function redirectWithFormState(string $message, string $mode, ?int $id = null, array $errors = [])
    {
        $redirect = redirect()
            ->to('/products')
            ->withInput()
            ->with('error', $message)
            ->with('form_mode', $mode)
            ->with('form_errors', $errors);

        if ($id !== null) {
            $redirect = $redirect->with('form_id', $id);
        }

        return $redirect;
    }

    public function index(): string
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
            'products' => $products,
            'pager' => $model->pager,
            'search' => $search,
            'rowOffset' => ($page - 1) * self::PER_PAGE,
        ]);
    }

    public function create()
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
                $this->validator->getErrors()
            );
        }

        $model = new ProductModel();
        $createdProductId = (int) $model->insert($product, true);

        return redirect()
            ->to('/products?' . http_build_query([
                'q' => $product['product_name'],
                'created_product_id' => $createdProductId,
            ]))
            ->with('success', 'Product created.');
    }

    public function update(int $id)
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
                $this->validator->getErrors()
            );
        }

        $model->update($id, $product);

        return redirect()->to('/products')->with('success', 'Product updated.');
    }

    public function delete(int $id)
    {
        $model = new ProductModel();
        $model->delete($id);

        return redirect()->to('/products')->with('success', 'Product deleted.');
    }
}
