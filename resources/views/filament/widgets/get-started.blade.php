<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Get started
        </x-slot>
        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
            <p><strong>1. Sites (domain &amp; script)</strong> — Add a site, set your domain(s), and copy the embed script to paste in your site’s <code class="rounded bg-gray-100 dark:bg-gray-800 px-1">&lt;head&gt;</code>.</p>
            <p><strong>2. Blocks (widgets)</strong> — Create blocks for that site (e.g. Shopify Add to Cart Counter) and configure where and how they appear.</p>
            <p><strong>3. Analytics</strong> — View events and add-to-cart counts per site.</p>
        </div>
        <x-slot name="footerActions">
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\SiteResource::getUrl('index') }}" color="primary">
                Open Sites
            </x-filament::button>
            <x-filament::button tag="a" href="{{ \App\Filament\Resources\BlockResource::getUrl('index') }}" color="gray">
                Open Blocks
            </x-filament::button>
        </x-slot>
    </x-filament::section>
</x-filament-widgets::widget>
