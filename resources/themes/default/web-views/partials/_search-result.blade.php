<ul class="list-group list-group-flush">
    @foreach($products as $product)
        <li class="list-group-item px-0 overflow-hidden">
            <a href="{{route('product',$product->slug)}}">
            <span class="search-result-product btn p-0 m-0 search-result-product-button align-items-baseline text-start">
                <span><i class="czi-search"></i></span>
                <div class="text-truncate">{{$product['name']}}</div>
            </span>
            </a>
        </li>
    @endforeach
</ul>
