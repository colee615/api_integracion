@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
    @if (session('status') && ! session()->has('new_token'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var message = @json(session('status'));

                if (window.Swal) {
                    window.Swal.fire({
                        title: 'Operación completada',
                        html: `
                            <div style="display:flex;flex-direction:column;align-items:center;text-align:center;">
                                <div style="width:74px;height:74px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#eff6ff 0%,#dbeafe 100%);margin-bottom:18px;">
                                    <div style="width:46px;height:46px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#1f4fb8;color:#fff;font-size:22px;font-weight:700;">✓</div>
                                </div>
                                <div style="max-width:360px;color:#5a7bbd;font-size:16px;line-height:1.55;">${message}</div>
                            </div>
                        `,
                        confirmButtonText: 'Continuar',
                        confirmButtonColor: '#1f4fb8',
                        background: '#ffffff',
                        width: 560,
                        padding: '2.2rem 2rem 1.8rem',
                        customClass: {
                            popup: 'corp-swal-popup',
                            title: 'corp-swal-title',
                            confirmButton: 'corp-swal-confirm'
                        },
                        buttonsStyling: true,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown animate__faster'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp animate__faster'
                        }
                    });
                    return;
                }

                window.alert(message);
            });
        </script>
    @endif
@stop
