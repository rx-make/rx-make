<div class="x_page-header">
    <h1>
        {{ $title }}
        @if ($description !== null)
            <a
                href="#aboutModule{{ $uid = uniqid() }}"
                class="x_icon-question-sign"
                data-toggle
            >
                {{ $lang->get('common.help') }}
            </a>
        @endif
    </h1>
</div>

@if ($description !== null)
    <p
        id="aboutModule{{ $uid }}"
        class="x_alert x_alert-info"
        hidden
    >
        {{ $description }}
    </p>
@endif

@include ('./XeValidatorMessage')
