<h1>Order Confirmation</h1>

<p>Request ID: {{ $requestId }}</p>
<p>Total: ${{ number_format($totalAmount, 2) }}</p>

<table cellpadding="6" cellspacing="0" border="1">
    <thead>
    <tr>
        <th>Product</th>
        <th>Variant</th>
        <th>Qty</th>
        <th>Unit</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($items as $item)
        <tr>
            <td>{{ $item['product_id'] }}</td>
            <td>{{ $item['variant_id'] }}</td>
            <td>{{ $item['quantity'] }}</td>
            <td>${{ number_format((float) $item['unit_price'], 2) }}</td>
            <td>${{ number_format((float) $item['total_price'], 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
