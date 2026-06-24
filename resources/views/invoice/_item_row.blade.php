<div class="item-row bg-slate-50 rounded-xl p-4 border border-slate-200">
    <div class="flex items-start gap-3">
        <div class="flex-1 space-y-3">
            <div class="form-group !mb-0">
                <label class="text-xs text-slate-500 mb-1 block">Producto (opcional)</label>
                <select class="input product-select" name="items[{{ $index }}][product_id]">
                    <option value="">— Ítem libre (sin producto) —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-price="{{ $product->price }}"
                                data-tax="{{ $product->tax }}"
                                @selected(($item['product_id'] ?? '') == $product->id)>
                            {{ $product->name }} — ${{ number_format($product->price, 0) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group !mb-0">
                <label class="text-xs text-slate-500 mb-1 block">Descripción</label>
                <input type="text" name="items[{{ $index }}][description]" placeholder="Descripción del ítem"
                       value="{{ $item['description'] ?? '' }}" required class="input description">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3">
                <div class="col-span-1 sm:col-span-2">
                    <label class="text-xs text-slate-500 mb-1 block">Cantidad</label>
                    <input type="number" name="items[{{ $index }}][quantity]" step="0.01" min="0.01"
                           value="{{ $item['quantity'] ?? 1 }}" required class="input quantity">
                </div>
                <div class="col-span-1 sm:col-span-3">
                    <label class="text-xs text-slate-500 mb-1 block">Precio Unit.</label>
                    <input type="number" name="items[{{ $index }}][unit_price]" step="0.01" min="0"
                           value="{{ $item['unit_price'] ?? '' }}" required class="input unit-price">
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="text-xs text-slate-500 mb-1 block">IVA %</label>
                    <input type="number" name="items[{{ $index }}][tax]" step="0.01" min="0" max="100"
                           value="{{ $item['tax'] ?? 0 }}" class="input tax">
                </div>
                <div class="col-span-1 sm:col-span-4">
                    <label class="text-xs text-slate-500 mb-1 block">Subtotal</label>
                    <div class="input bg-white font-medium subtotal-display flex items-center">$0.00</div>
                </div>
                <div class="col-span-1 sm:col-span-1 flex items-end">
                    <button type="button" class="remove-item w-full inline-flex items-center justify-center p-2.5 text-slate-400 hover:text-red-500 hover:bg-red-50:bg-red-900/20 rounded-lg transition-colors" title="Eliminar ítem" aria-label="Eliminar ítem">
                        <x-icon name="trash" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
