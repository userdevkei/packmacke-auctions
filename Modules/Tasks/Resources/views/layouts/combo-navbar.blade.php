@extends('tasks::layouts.layout')

@section('main-content')
  @include('tasks::navbars.navbar-vertical')
  <div class="content">
    @include('tasks::navbars.navbar-combo')
    @yield('tasks::dashboard')
    @include('tasks::partials.footer')
  </div>
@endsection
