@if (! empty($data['content']))
    <section @class(['py-6' => $narrow, 'container-narrow py-14 lg:py-20' => ! $narrow])>
        <div class="prose prose-lg prose-wh">{!! \App\Support\RichText::normalize($data['content']) !!}</div>
    </section>
@endif
