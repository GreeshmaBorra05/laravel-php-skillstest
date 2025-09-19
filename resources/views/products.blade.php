<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>PHP Skills Test — Products</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding: 24px; }
    .table td, .table th { vertical-align: middle; }
    .w-120 { width:120px; }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="mb-4">Inventory</h1>

    {{-- Form --}}
    <div class="card mb-4">
      <div class="card-body">
        <form id="productForm" class="row gy-3 gx-3">
          <input type="hidden" id="editId">
          <div class="col-md-4">
            <label class="form-label">Product name</label>
            <input class="form-control" id="name" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Quantity in stock</label>
            <input type="number" min="0" class="form-control" id="quantity" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Price per item</label>
            <input type="number" step="0.01" min="0" class="form-control" id="price" required>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100" type="submit" id="submitBtn">Add</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
      <table class="table table-striped align-middle" id="productsTable">
        <thead class="table-dark">
          <tr>
            <th>Product name</th>
            <th class="text-end">Quantity</th>
            <th class="text-end">Price</th>
            <th>Datetime submitted</th>
            <th class="text-end">Total value</th>
            <th class="w-120">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
          <tr class="fw-bold">
            <td colspan="4" class="text-end">Grand Total</td>
            <td class="text-end" id="grandTotal">$0.00</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="mt-3">
      <a class="btn btn-outline-secondary" href="{{ route('products.exportXml') }}" target="_blank">
        Export XML (optional)
      </a>
    </div>
  </div>

  <script>
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const form = document.getElementById('productForm');
    const btn  = document.getElementById('submitBtn');
    const editId = document.getElementById('editId');

    async function fetchList() {
      const res = await fetch('{{ route('products.list') }}');
      const data = await res.json();
      renderTable(data.items, data.grand_total);
    }

    function currency(n){ return '$' + Number(n).toFixed(2); }

    function renderTable(items, grand) {
      const tbody = document.querySelector('#productsTable tbody');
      tbody.innerHTML = items.map(p => `
        <tr data-id="${p.id}">
          <td>${p.name}</td>
          <td class="text-end">${p.quantity}</td>
          <td class="text-end">${currency(p.price)}</td>
          <td>${new Date(p.submitted_at).toLocaleString()}</td>
          <td class="text-end">${currency(p.total_value)}</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" onclick="startEdit('${p.id}', '${p.name.replace(/"/g, '&quot;')}', ${p.quantity}, ${p.price})">Edit</button>
          </td>
        </tr>
      `).join('');
      document.getElementById('grandTotal').textContent = currency(grand);
    }

    window.startEdit = function(id, name, qty, price) {
      editId.value = id;
      document.getElementById('name').value = name;
      document.getElementById('quantity').value = qty;
      document.getElementById('price').value = price;
      btn.textContent = 'Update';
    };

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        name: document.getElementById('name').value.trim(),
        quantity: Number(document.getElementById('quantity').value),
        price: Number(document.getElementById('price').value),
      };

      let url   = '{{ route('products.store') }}';
      let method = 'POST';
      if (editId.value) {
        url = '{{ url('/products') }}/' + editId.value;
        method = 'PUT';
      }

      const res = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload)
      });

      if (!res.ok) {
        alert('Validation error — please check your inputs.');
        return;
      }

      const data = await res.json();
      renderTable(data.items, data.grand_total);

      form.reset();
      editId.value = '';
      btn.textContent = 'Add';
    });

    fetchList();
  </script>
</body>
</html>
