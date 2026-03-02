@extends('tasks::layouts.layout')

@section('main-content')
  @include('tasks::navbars.navbar-top-menu')
  <div class="content">
      @yield('tasks::dashboard')
      @include('tasks::partials.footer')
  </div>
@endsection
