@extends('tasks::layouts.layout')
@section('main-content')
  @include('tasks::navbars.navbar-vertical')
  <div class="content">
    @include('tasks::navbars.navber-top-default')
      @yield('tasks::dashboard')
      @include('tasks::partials.footer')
  </div>
@endsection
