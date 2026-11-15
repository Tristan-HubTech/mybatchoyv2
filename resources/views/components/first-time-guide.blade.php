@php
    $isOwnerGuide = ($userRole ?? null) === 'owner';
    $guideSteps = [
        [
            'icon' => '🏠',
            'title' => '1. Check the Dashboard First',
            'desc' => 'See how many items are in stock, which ingredients are running low or out of stock, and what needs attention today.',
        ],
        [
            'icon' => '🔍',
            'title' => '2. Find Any Item in Manage Stock (Inventory)',
            'desc' => 'Click Inventory in the menu, then type the ingredient name (like "Pork" or "Miki") in the search box or pick a category.',
        ],
        [
            'icon' => '➕',
            'title' => '3. Add Stock When Deliveries Arrive',
            'desc' => 'Go to Stock Transactions → Add Stock. Pick the item, type how much arrived, add the supplier or receipt note, and save.',
        ],
        [
            'icon' => '➖',
            'title' => '4. Remove Stock When Used in Kitchen',
            'desc' => 'Go to Stock Transactions → Remove Stock. Choose the item, enter the amount taken out, check what will remain, and confirm.',
        ],
        [
            'icon' => '🗑️',
            'title' => '5. Record Spoiled or Damaged Ingredients',
            'desc' => 'Go to Stock Transactions → Record Spoilage. Pick the spoiled item, type the quantity and reason, and confirm to deduct it from stock.',
        ],
        [
            'icon' => '📜',
            'title' => '6. View Stock History Anytime',
            'desc' => 'Open Stock Transactions to review every stock addition, removal, correction, and spoilage with dates and staff names.',
        ],
    ];
@endphp

<!-- First-Time User Quick Guide Banner (dismissible, saved in browser) -->
<div id="firstTimeGuideBanner" class="card p-3 p-md-4 mb-4 d-none" style="border-color: rgba(255, 193, 7, 0.45); background: linear-gradient(180deg, rgba(255, 193, 7, 0.08), rgba(27, 27, 27, 0.98));">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <span class="badge bg-warning text-dark mb-1">Quick Start Guide</span>
            <h2 class="h5 fw-bold mb-1 text-white">New to MyBatchoy? Here is how to get started</h2>
            <p class="muted small mb-0">Follow these simple steps anytime. You can hide this card once you feel comfortable and reopen it later from the <strong>Quick Help</strong> button at the top.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-light" onclick="openHelpGuideModal()">Open Step-by-Step Guide</button>
            <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold" onclick="dismissFirstTimeGuide(true)">Don't show again</button>
        </div>
    </div>

    <div class="row g-2">
        @foreach(array_slice($guideSteps, 0, 3) as $step)
            <div class="col-md-4">
                <div class="p-2 px-3 rounded-3 h-100" style="background: rgba(17, 17, 17, 0.72); border: 1px solid var(--border);">
                    <div class="fw-semibold text-white small mb-1">{{ $step['icon'] }} {{ $step['title'] }}</div>
                    <div class="muted" style="font-size: 0.82rem; line-height: 1.4;">{{ $step['desc'] }}</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Full Step-by-Step Guide Modal -->
<div class="modal fade" id="helpGuideModal" tabindex="-1" aria-labelledby="helpGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background: #1B1B1B; border: 1px solid var(--border); color: var(--text);">
            <div class="modal-header border-border">
                <div>
                    <span class="badge bg-warning text-dark mb-1">MyBatchoy User Guide</span>
                    <h5 class="modal-title fw-bold" id="helpGuideModalLabel">Simple Step-by-Step Instructions</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="muted mb-3">Every tool in MyBatchoy is designed to keep your kitchen stock accurate without technical steps.</p>
                <div class="row g-3">
                    @foreach($guideSteps as $step)
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 h-100" style="background: #111111; border: 1px solid var(--border);">
                                <h6 class="fw-bold text-white mb-1">{{ $step['icon'] }} {{ $step['title'] }}</h6>
                                <p class="muted small mb-0" style="line-height: 1.5;">{{ $step['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 p-3 rounded-3" style="background: rgba(229, 57, 53, 0.08); border: 1px solid rgba(229, 57, 53, 0.3);">
                    <div class="fw-bold text-white small mb-1">&#128737;&#65039; Your Role: {{ $isOwnerGuide ? 'Owner / Admin (Full Access)' : 'Staff Member (Daily Stock Tasks)' }}</div>
                    <div class="muted small">
                        @if($isOwnerGuide)
                            You can manage inventory items, record stock additions and removals, manage suppliers and supply purchases, check reports and activity history, and approve or manage staff accounts.
                        @else
                            You can check current stock levels, add new deliveries, remove ingredients used in the kitchen, correct shelf counts, and record spoilage. Admin tools and business configuration are reserved for the Owner/Admin.
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer border-border d-flex justify-content-between">
                <button type="button" class="btn btn-outline-light btn-sm" onclick="dismissFirstTimeGuide(true)" data-bs-dismiss="modal">Hide Welcome Banner</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got It, Close</button>
            </div>
        </div>
    </div>
</div>
