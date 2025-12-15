Order Confirmation
Request ID: {{ $requestId }}

Total: ${{ number_format($totalAmount, 2) }}

@foreach ($items as $item)
    - product={{ $item['product_id'] }} variant={{ $item['variant_id'] }} qty={{ $item['quantity'] }} unit=${{ number_format((float) $item['unit_price'], 2) }} total=${{ number_format((float) $item['total_price'], 2) }}
@endforeach
