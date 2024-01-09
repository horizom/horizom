@extends('default')

@section('content')
<div class="block">
    <img src="{{ asset('img/logo.svg') }}" alt="logo" width="30%">
    <h1>Horizom Framework</h1>
    <code class="version">
        <p class="comment">// Welcome to the lightness PHP framework.</p>
        <p>Version: <span>{{ HORIZOM_VERSION }}</span>&nbsp;- PHP: <span>{{ PHP_VERSION }}</span></p>
    </code>

    <div class="menu">
        <ul>
            <li>
                <a href="https://horizom.github.io/" title="See the documentation" target="blank">Documentation</a>
            </li>
            <li>
                <a href="https://github.com/horizom/app" title="Got to repository" target="blank">GitHub</a>
            </li>
        </ul>
    </div>
</div>
@endsection