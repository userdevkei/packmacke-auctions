@extends('client::layouts.layout')

@section('main-content')
  @include('client::navbars.navbar-top-menu')
  <div class="content">
      @yield('client::dashboard')
      @include('client::partials.footer')
  </div>
@endsection
