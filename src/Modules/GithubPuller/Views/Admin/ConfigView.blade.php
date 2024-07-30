@load ('../../lang')
@load ('./assets/js/config.js', 'body')

@php
    use RxMake\Facade\Lang;
    use RxMake\Module\Admin\AdminComponent;
    $ln = Lang::getLangSet('RxMake\\Modules\\GithubPuller\\');
@endphp

{{ AdminComponent::header(($ln) ('module_info_name'), ($ln) ('module_info_description')) }}

<form
        method="POST"
        action="/"
        enctype="multipart/form-data"
        class="x_form-horizontal"
>
    {{ AdminComponent::formInjection('/') }}

    <section class="section">
        <h2>{{ ($ln) ('admin_basic_configuration_title')  }}</h2>
        <div class="x_control-group">
            <label
                    for="secretKey"
                    class="x_control-label"
            >
                {{ ($ln) ('admin_basic_configuration_secret_key_title') }}
            </label>
            <div class="x_controls">
                <input
                        id="secretKey"
                        name="secretKey"
                        type="password"
                        placeholder="{{ ($ln) ('admin_basic_configuration_secret_key_title') }}"
                        value="{{ $config->secretKey }}"
                        style="width: 400px; max-width: 100%"
                />
                <button
                        type="button"
                        class="x_btn"
                >
                    <i
                            class="x_icon-refresh"
                            style="transform: translateY(1px); margin-right: 2px"
                    ></i>
                    {{ ($ln) ('admin_basic_configuration_secret_key_randomize') }}
                </button>
                <p
                        class="x_help-block"
                        style="margin-top: 6px"
                >
                    {{ ($ln) ('admin_basic_configuration_secret_key_description') }}
                </p>
            </div>
        </div>
    </section>

    {{ AdminComponent::formSubmit() }}
</form>
