<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

/**
 * Editorial copy for regions, taken from the WordPress homepage.
 * Only fills fields that are still empty, so admin edits are never overwritten.
 */
class DestinationCopySeeder extends Seeder
{
    public function run(): void
    {
        $copy = [
            'everest-region' => ['Sherpa culture beneath the roof of the world', 'The Everest Region is known for its awe-inspiring landscapes and the towering Everest peak, the highest in the world. It offers an unforgettable trekking experience amidst majestic mountains and Sherpa culture.', ['Everest Base Camp', 'Island Peak', 'Gokyo Lakes', 'Three High Passes']],
            'annapurna-region' => ['Scenic trails, rich culture', 'The Annapurna Region is famous for its diverse ecosystems, stunning mountain vistas, and accessible trekking routes. With vibrant villages and rich biodiversity, it’s perfect for trekkers seeking variety.', ['Ghorepani Poon Hill', 'Annapurna Circuit', 'Mardi Himal', 'Upper Mustang']],
            'manaslu-region' => ['Remote trails, real adventure', 'The Manaslu Region offers an off-the-beaten-path adventure with stunning views of Manaslu, one of the world’s highest mountains. It is known for peaceful trekking routes and diverse wildlife.', ['Manaslu Circuit', 'Tsum Valley', 'Manaslu & Tsum Valley', 'Manaslu Base Camp']],
            'langtang-region' => ['Big mountains, close to Kathmandu', 'The Langtang Region is perfect for those seeking shorter treks with incredible views of the Langtang Himalaya. It’s also known for its diverse wildlife, lush forests, and proximity to Kathmandu.', ['Langtang Valley', 'Gosaikunda & Helambu', 'Yala Peak', 'Ganja La Pass']],
            'makalu-region' => ['Wild valleys beneath the fifth-highest peak', 'A true wilderness trek through the Barun Valley to the foot of Makalu — rhododendron forests, high glaciers and very few other trekkers.', ['Makalu Base Camp', 'Barun Valley']],
            'kanchenjunga-region' => ['Nepal’s last great wilderness', 'Kanchenjunga sits in the far eastern corner of Nepal, untouched by mass tourism and unchanged by time. No crowds, no noise — just raw Himalayan wilderness the way it used to be.', ['Kanchenjunga Circuit', 'North & South Base Camps']],
            'dolpo-region' => ['The hidden land beyond the Himalaya', 'Remote, arid and deeply Tibetan in culture, Dolpo is one of the least-visited corners of Nepal — turquoise lakes, ancient gompas and high passes.', ['Shey Phoksundo Lake', 'Upper Dolpo']],
            'nepal' => ['From jungle lowlands to the highest peaks on earth', 'Eight of the world’s fourteen 8,000-metre peaks, living Hindu and Buddhist heritage, and some of the finest trekking anywhere — Nepal is our home, and the heart of everything we do.', ['Everest', 'Annapurna', 'Manaslu', 'Langtang']],
            'tibet' => ['Monasteries at the edge of the sky', 'From the monastery corridors of Lhasa to the sacred circuit of Mount Kailash, Tibet is a journey across the highest plateau on earth.', ['Lhasa', 'Mount Kailash', 'Everest North Base Camp']],
            'bhutan' => ['The kingdom of happiness', 'Fortress dzongs, prayer-flag passes and the cliff-hung Tiger’s Nest — Bhutan rewards the traveller with a culture that has chosen its own path.', ['Paro & Tiger’s Nest', 'Thimphu', 'Punakha']],
            'india' => ['Ladakh, Sikkim & the Indian Himalaya', 'Monastery-dotted valleys of Ladakh and the tea-hill trails of Sikkim and Darjeeling — combined easily with Nepal.', ['Ladakh', 'Sikkim', 'Darjeeling']],
        ];

        $sort = 0;
        foreach ($copy as $slug => [$tagline, $intro, $highlights]) {
            $destination = Destination::where('slug', $slug)->first();
            if (! $destination) {
                continue;
            }

            $destination->tagline = $destination->tagline ?: $tagline;
            $destination->intro = $destination->intro ?: $intro;
            $destination->highlights = $destination->highlights ?: $highlights;
            $destination->sort = $destination->sort ?: ++$sort;
            $destination->save();
        }
    }
}
