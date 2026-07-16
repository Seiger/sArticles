@php
    $items = collect(is_array($value['items'] ?? null) ? $value['items'] : [])
        ->filter(fn ($item) => is_array($item))
        ->map(function (array $item) {
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            $plainAnswer = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($answer), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

            return compact('question', 'answer', 'plainAnswer');
        })
        ->filter(fn (array $item) => $item['question'] !== '' && $item['plainAnswer'] !== '')
        ->values();

    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $items->map(fn (array $item) => [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['plainAnswer'],
            ],
        ])->all(),
    ];
@endphp

@if($items->isNotEmpty())
    <section class="faq article-faq">
        <h2 class="h2-title">FAQ</h2>
        <div class="faq__text">
            @foreach($items as $item)
                <h3>{{$item['question']}}</h3>
                <div class="faq__answer">{!!$item['answer']!!}</div>
            @endforeach
        </div>
        <script type="application/ld+json">{!!json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)!!}</script>
    </section>
@endif
