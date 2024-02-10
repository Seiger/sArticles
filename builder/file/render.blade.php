@if(trim($value['file'] ?? ''))
    <section>
        @if(trim($value['icon'] ?? ''))
            <img src="{{$value['icon'] ?? ''}}" alt="Download {{$value['title'] ?? ''}}"/>
        @endif
        <a href="{{$value['file'] ?? ''}}" download>{{$value['title'] ?? ''}}</a>
    </section>
@endif