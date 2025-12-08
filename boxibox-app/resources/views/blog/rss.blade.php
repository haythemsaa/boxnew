<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>Blog Boxibox - Self-Stockage</title>
        <link>{{ url('/blog') }}</link>
        <description>Actualités, conseils et tendances du self-stockage - Boxibox</description>
        <language>{{ app()->getLocale() }}</language>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ url('/blog/rss') }}" rel="self" type="application/rss+xml"/>
        <image>
            <url>{{ url('/images/logo.png') }}</url>
            <title>Boxibox</title>
            <link>{{ url('/') }}</link>
        </image>
        @foreach($posts as $post)
        <item>
            <title><![CDATA[{{ $post->title }}]]></title>
            <link>{{ $post->url }}</link>
            <guid isPermaLink="true">{{ $post->url }}</guid>
            <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
            @if($post->author)
            <author>{{ $post->author->email }} ({{ $post->author->name }})</author>
            @endif
            @if($post->category)
            <category><![CDATA[{{ $post->category->name }}]]></category>
            @endif
            <description><![CDATA[{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}]]></description>
            <content:encoded><![CDATA[{!! $post->content !!}]]></content:encoded>
            @if($post->featured_image)
            <enclosure url="{{ url($post->featured_image) }}" type="image/jpeg"/>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
