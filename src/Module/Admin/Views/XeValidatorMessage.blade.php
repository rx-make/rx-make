@if (Context::get('XE_VALIDATOR_MESSAGE'))
    <div class="message {{ Context::get('XE_VALIDATOR_MESSAGE_TYPE') ?? '' }}">
        <p>
            {{ $lang->{Context::get('XE_VALIDATOR_MESSAGE')} }}
        </p>
    </div>
@endif
