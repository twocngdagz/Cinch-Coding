Order Confirmation

Customer Email: {{ $email }}

Items:
@foreach ($items as $item)
- Product ID: {{ $item['product_id'] }}
  Variant ID: {{ $item['variant_id'] }}
  Quantity: {{ $item['quantity'] }}
  Unit Price: {{ $item['unit_price'] }}
  Total Price: {{ $item['total_price'] }}

@endforeach
Total Amount: {{ $totalAmount }}

