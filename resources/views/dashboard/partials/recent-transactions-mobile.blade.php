<div class="d-md-none eb-mobile-tx-list">
    @foreach($transactions as $tx)
        @php
            $txType = $tx->transaction_type;
            $iconClass = match ($txType) {
                'stock_in' => 'tx-in',
                'stock_out' => 'tx-out',
                'adjustment' => 'tx-adj',
                'spoilage' => 'tx-spoil',
                default => 'tx-out',
            };
            $typeLabel = match ($txType) {
                'stock_in' => 'Stock In',
                'stock_out' => 'Stock Out',
                'adjustment' => 'Adjustment',
                'spoilage' => 'Spoilage',
                default => ucfirst(str_replace('_', ' ', $txType)),
            };
            $isPositive = $txType === 'stock_in'
                || ($txType === 'adjustment' && (float) $tx->updated_quantity >= (float) $tx->previous_quantity);
            $accentColor = match ($txType) {
                'stock_in' => '#4CAF50',
                'adjustment' => '#FFC107',
                'spoilage' => '#FF8A80',
                default => '#E53935',
            };
            $unitAbbr = $tx->inventoryItem?->unit?->short_name ?? '';
            $txDate = $tx->transaction_date ?? $tx->created_at;
        @endphp
        <div class="eb-mobile-tx-row">
            <div class="eb-mobile-tx-left">
                <span class="eb-mobile-tx-icon {{ $iconClass }}" aria-hidden="true">
                    @if($txType === 'adjustment')
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                    @elseif($txType === 'spoilage')
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    @else
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                        </svg>
                    @endif
                </span>
                <div class="eb-mobile-tx-text">
                    <div class="eb-mobile-tx-type" style="color: {{ $accentColor }};">{{ $typeLabel }}</div>
                    <div class="eb-mobile-tx-item">{{ $tx->inventoryItem->name ?? 'Deleted Item' }}</div>
                </div>
            </div>
            <div class="eb-mobile-tx-right">
                <div class="eb-mobile-tx-qty" style="color: {{ $accentColor }};">
                    {{ $isPositive ? '+' : '-' }}{{ rtrim(rtrim(number_format((float) $tx->quantity, 2), '0'), '.') ?: '0' }}{{ $unitAbbr ? ' ' . $unitAbbr : '' }}
                </div>
                <div class="eb-mobile-tx-date">{{ $txDate ? $txDate->format('M d, H:i') : '—' }}</div>
            </div>
        </div>
    @endforeach
</div>

