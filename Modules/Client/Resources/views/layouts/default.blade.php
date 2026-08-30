@extends('client::layouts.layout')

@section('main-content')
  @include('client::navbars.navbar-vertical')
  <div class="content">
    @include('client::navbars.navber-top-default')
      @yield('client::dashboard')
      @include('client::partials.footer')
  </div>
@endsection
