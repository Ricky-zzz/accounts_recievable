<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
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

        return view('products/index', [
            'products' => $model->orderBy('product_name', 'asc')->findAll(),
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
        $model->insert($product);

        return redirect()->to('/products')->with('success', 'Product created.');
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
