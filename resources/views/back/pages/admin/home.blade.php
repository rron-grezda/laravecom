@extends('back.layout.pages-layout')
@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Home')
@section('content')
<header>
        <h1>Welcome to Our Page</h1>
</header>
<br>
<main>
    <p>Hello there! Enjoy your stay.</p>
</main>

    
@endsection