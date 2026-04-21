<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Products extends BaseController
{
    public function index(): string
    {
        $model = new ProductModel();

        return view('products/index', [
            'products' => $model->orderBy('product_name', 'asc')->findAll(),
        ]);
    }

    public function createForm(): string
    {
        return view('products/form', [
            'title' => 'New Product',
            'action' => base_url('products'),
            'product' => [
                'product_id' => '',
                'product_name' => '',
                'unit_price' => '',
            ],
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
            return view('products/form', [
                'title' => 'New Product',
                'action' => base_url('products'),
                'product' => $product,
                'validation' => $this->validator,
            ]);
        }

        $model = new ProductModel();
        $model->insert($product);

        return redirect()->to('/products')->with('success', 'Product created.');
    }

    public function edit(int $id): string
    {
        $model = new ProductModel();
        $product = $model->find($id);

        if (! $product) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('products/form', [
            'title' => 'Edit Product',
            'action' => base_url('products/' . $id),
            'product' => $product,
        ]);
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
            return view('products/form', [
                'title' => 'Edit Product',
                'action' => base_url('products/' . $id),
                'product' => array_merge($existing, $product),
                'validation' => $this->validator,
            ]);
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
