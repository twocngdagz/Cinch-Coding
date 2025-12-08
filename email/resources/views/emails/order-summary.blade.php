<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Order Confirmation</h1>

    <p>Customer Email: {{ $email }}</p>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Variant ID</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['product_id'] }}</td>
                    <td>{{ $item['variant_id'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['unit_price'] }}</td>
                    <td>{{ $item['total_price'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Total Amount: {{ $totalAmount }}</strong></p>
</body>
</html>

