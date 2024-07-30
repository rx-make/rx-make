<div class="x_page-header">
    <h1>
        {{ $title }}
        <span class="path">
            @foreach ($path ?? [] as $name => $url)
                &gt;
                <a
                    href="{{ $url }}"
                    target="_blank"
                >
                    {{ $name }}
                </a>
            @endforeach
        </span>
    </h1>
</div>
