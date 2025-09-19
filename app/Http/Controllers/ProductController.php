<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

class ProductController extends Controller
{
    private string $jsonPath = 'products.json';

    public function index()
    {
        // Ensure JSON file exists
        if (!Storage::exists($this->jsonPath)) {
            Storage::put($this->jsonPath, json_encode([]));
        }
        return view('products'); // resources/views/products.blade.php
    }

    public function list()
    {
        $items = $this->read();
        // Order by submitted_at DESC
        usort($items, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));
        return response()->json([
            'items' => $items,
            'grand_total' => array_sum(array_column($items, 'total_value')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price'    => 'required|numeric|min:0',
        ]);

        $items = $this->read();

        $item = [
            'id'           => (string) Str::ulid(),
            'name'         => $data['name'],
            'quantity'     => (int) $data['quantity'],
            'price'        => (float) $data['price'],
            'submitted_at' => now()->toIso8601String(),
            'total_value'  => (int)$data['quantity'] * (float)$data['price'],
        ];

        $items[] = $item;
        $this->write($items);

        return $this->list();
    }

    public function update(string $id, Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'price'    => 'required|numeric|min:0',
        ]);

        $items = $this->read();

        foreach ($items as &$p) {
            if ($p['id'] === $id) {
                $p['name']        = $data['name'];
                $p['quantity']    = (int) $data['quantity'];
                $p['price']       = (float) $data['price'];
                $p['total_value'] = $p['quantity'] * $p['price'];
                break;
            }
        }

        $this->write($items);
        return $this->list();
    }

    // Bonus: XML export
    public function exportXml()
    {
        $items = $this->read();
        usort($items, fn($a, $b) => strcmp($b['submitted_at'], $a['submitted_at']));

        $xml = new SimpleXMLElement('<products/>');
        foreach ($items as $row) {
            $node = $xml->addChild('product');
            foreach ($row as $k => $v) {
                $node->addChild($k, htmlspecialchars((string) $v));
            }
        }

        return response($xml->asXML(), 200)->header('Content-Type', 'application/xml');
    }

    // Helpers
    private function read(): array
    {
        if (!Storage::exists($this->jsonPath)) {
            return [];
        }
        return json_decode(Storage::get($this->jsonPath), true) ?: [];
    }

    private function write(array $items): void
    {
        Storage::put($this->jsonPath, json_encode($items, JSON_PRETTY_PRINT));
    }
}
