@extends('account::layouts.layout')
@section('main-content')
  @include('account::navbars.navbar-vertical')
  <div class="content">
    @include('account::navbars.navber-top-default')
      @yield('account::dashboard')
      @include('account::partials.footer')
  </div>
  <script>
      document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('.js-choice').forEach(function (el) {
              new Choices(el, {
                  shouldSort: false,
                  searchEnabled: true,
                  itemSelectText: '',
              });
          });
      });
  </script>

@endsection
