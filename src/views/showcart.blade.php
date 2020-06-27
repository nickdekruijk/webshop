<table class="webshop-cart-table" width="100%">
    <tr>
        <th class="webshop-cart-title" align="left">{{ trans('webshop::cart.product') }}</th>
        <th class="webshop-cart-price" align="right">{{ trans('webshop::cart.price') }}</th>
        <th class="webshop-cart-quantity" align="center">{{ trans('webshop::cart.quantity') }}</th>
        <th class="webshop-cart-total" align="right">{{ trans('webshop::cart.total') }}</th>
    </tr>

    @foreach ($items->items as $item) 
        <tr>
            @if (isset($item->shipping_options))

                <td colspan="3"><div class="webshop-cart-title">
                    @if (count($item->shipping_options) == 1)
                        {{ current($item->shipping_options) }}
                        @if (!$hide_interaction)
                            <input type="hidden" name="webshop-shipping" value="{{ array_key_first($item->shipping_options) }}">
                        @endif
                    @elseif (count($item->shipping_options) > 1)
                        @if (!$hide_interaction)
                            <div class="select webshop-shipping"><select name="webshop-shipping" onchange="this.form.submit()"{{ $errors->has('webshop-shipping') ? ' class=error' : '' }}>
                                <option value="">{{ trans('webshop::cart.select-shipping') }}</option>
                                @foreach($item->shipping_options as $rate_id => $rate_title)
                                    <option value="{{ $rate_id }}"{{ Webshop::old('webshop-shipping') == $rate_id ? ' selected' : '' }}>{{ $rate_title }}</option>
                                @endforeach
                            </select></div>
                        @else
                            {{ $item->title }}
                        @endif
                    @else
                        {{ trans('webshop::cart.no-shipping-possible') }}
                    @endif
                </div></td>

            @elseif ($item->id || $item->quantity != 1)

                <td><div class="webshop-cart-title">{!! $item->title !!}</div></td>
                <td class="webshop-cart-price" nowrap align="right">{{ Webshop::money($vat_show < 2 ? $item->price->price_including_vat : $item->price->price_excluding_vat) }}</td>
                <td class="webshop-cart-quantity" align="center">
                    @if ($hide_interaction)
                        {{ +$item->quantity }}
                    @else
                        <input onchange="this.form.submit()" type="number" name="quantity_{{ $item->id }}" min="0" value="{{ +$item->quantity }}">
                    @endif
                </td>

            @else

                <td colspan="3"><div class="webshop-cart-title">{!! $item->title !!}</div></td>
                
            @endif

            <td class="webshop-cart-total" nowrap align="right">
                @if ($item->quantity ?? null)
                    {{ Webshop::money($item->quantity * ($vat_show < 2 ? $item->price->price_including_vat : $item->price->price_excluding_vat)) }}
                @endif
            </td>
        </tr>
    @endforeach

    @if ($vat_show >= 1)
        @if ($vat_show == 1)
            <tr>
                <td class="webshop-cart-title">{{ trans('webshop::cart.subtotal_vatIncl') }}</td>
                <td class="webshop-cart-price"></td>
                <td class="webshop-cart-quantity"></td>
                <td class="webshop-cart-total" nowrap align="right">{{ Webshop::money($items->amount_including_vat) }}</td>
            </tr>
        @endif
        <tr>
            <td class="webshop-cart-title">{{ trans('webshop::cart.subtotal_vatExcl') }}</td>
            <td class="webshop-cart-price"></td>
            <td class="webshop-cart-quantity"></td>
            <td class="webshop-cart-total" nowrap align="right">{{ Webshop::money($items->amount_excluding_vat) }}</td>
        </tr>

        @foreach($items->amount_vat as $perc => $vatcount)
            <tr>
                <td class="webshop-cart-title">{{ trans('webshop::cart.vat') }} {{ +$perc}}%</td>
                <td class="webshop-cart-price"></td>
                <td class="webshop-cart-quantity"></td>
                <td class="webshop-cart-total" nowrap align="right">{{ Webshop::money($vatcount) }}</td>
            </tr>
        @endforeach
    @endif

    <tr>
        <td class="webshop-cart-title">{{ trans('webshop::cart.total_to_pay') }}</td>
        <td class="webshop-cart-price"></td>
        <td class="webshop-cart-quantity"></td>
        <td class="webshop-cart-total" nowrap align="right">{{ Webshop::money($items->amount_including_vat) }}</td>
    </tr>
    
</table>