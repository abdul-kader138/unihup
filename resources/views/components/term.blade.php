@props(['k'])

@php($entry = \App\Support\Glossary::get($k))

@if ($entry)
    <abbr class="ui-term" title="{{ $entry['term'] }} — {{ $entry['definition'] }}">{{ $slot->isEmpty() ? $entry['term'] : $slot }}</abbr>
@else
    {{ $slot }}
@endif
