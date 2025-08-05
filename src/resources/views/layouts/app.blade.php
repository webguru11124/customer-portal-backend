<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Aptive</title>
  <script src="//unpkg.com/alpinejs" defer></script>
  <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body class="bg-gray-100">
  @if (env('APP_ENV') != 'production')
    <div class="fixed w-full p-0.5 top-0 left-0 {{ (env('APP_ENV') === 'local') ? 'bg-orange-400' : 'bg-teal-400' }} text-xs text-white text-center shadow-md bg-opacity-90">
      ENV: <strong>{{ env('APP_ENV') }}</strong>
    </div>
  @endif
  <div class="min-h-full">
    <nav class="shadow-sm bg-aptive-900" x-data="{ open: false }">
      <div class="container px-4 mx-auto max-w-7xl">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex items-center flex-shrink-0">
              <img class="block w-auto h-8 lg:hidden" src="{{ asset('img/logo_aptive.png') }}" alt="Aptive">
              <img class="hidden w-auto h-8 lg:block" src="{{ asset('img/logo_aptive.png') }}" alt="Aptive">
            </div>
          </div>
        </div>
      </div>
    </nav>

    <x-breadcrumbs :title="$title" />

    <div class="py-10">
      <header>
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            {{ $title ?? 'Aptive' }}
          </h1>
        </div>
      </header>
      <main>
        <div class="px-2 mx-auto sm:px-6 lg:max-w-7xl lg:px-8">
          {{ $slot }}
        </div>
      </main>
    </div>
  </div>
</body>

</html>
