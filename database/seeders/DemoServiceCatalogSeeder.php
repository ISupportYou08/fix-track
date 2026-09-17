<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use Illuminate\Database\Seeder;

class DemoServiceCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->services() as $category => $services) {
            foreach ($services as $code => $name) {
                ServiceCatalog::query()->updateOrCreate(['code' => $code], [
                    'name' => $name,
                    'category' => $category,
                    'base_price' => null,
                    'is_active' => true,
                    'description' => "Professional {$name} for your {$category} service needs.",
                ]);
            }
        }
    }

    /** @return array<string, array<string, string>> */
    private function services(): array
    {
        return [
            'Cooling' => [
                'aircon' => 'Aircon cleaning',
                'aircon-diagnosis' => 'Aircon diagnosis',
                'cooling-installation' => 'Aircon installation',
                'cooling-refrigerant' => 'Refrigerant recharge',
                'cooling-compressor' => 'Compressor repair',
                'cooling-thermostat' => 'Thermostat repair',
                'cooling-drainage' => 'Drainage cleaning',
                'cooling-duct' => 'Duct cleaning',
                'cooling-maintenance' => 'Preventive maintenance',
                'cooling-remote' => 'Remote control setup',
            ],
            'Appliances' => [
                'appliance' => 'Appliance repair',
                'appliance-diagnosis' => 'Appliance diagnosis',
                'appliance-washing-machine' => 'Washing machine repair',
                'appliance-refrigerator' => 'Refrigerator repair',
                'appliance-microwave' => 'Microwave repair',
                'appliance-oven' => 'Oven and stove repair',
                'appliance-dishwasher' => 'Dishwasher repair',
                'appliance-dryer' => 'Dryer repair',
                'appliance-vacuum' => 'Vacuum repair',
                'appliance-small' => 'Small appliance repair',
            ],
            'Electrical' => [
                'electrical' => 'Electrical repair',
                'electrical-diagnosis' => 'Electrical diagnosis',
                'electrical-outlet' => 'Outlet and switch repair',
                'electrical-breaker' => 'Circuit breaker replacement',
                'electrical-lighting' => 'Lighting installation',
                'electrical-wiring' => 'Wiring repair',
                'electrical-fan' => 'Ceiling fan repair',
                'electrical-generator' => 'Generator maintenance',
                'electrical-surge' => 'Surge protection setup',
                'electrical-inspection' => 'Electrical safety inspection',
            ],
            'Electronics' => [
                'electronics-battery' => 'Battery replacement',
                'electronics-diagnosis' => 'Not sure — diagnose my device',
                'electronics-lcd' => 'LCD / screen replacement',
                'electronics-smartphone' => 'Smartphone repair',
                'electronics-laptop' => 'Laptop repair',
                'electronics-tablet' => 'Tablet repair',
                'electronics-tv' => 'Television repair',
                'electronics-audio' => 'Audio system repair',
                'electronics-console' => 'Game console repair',
                'electronics-data-recovery' => 'Data recovery service',
            ],
            'Plumbing' => [
                'plumbing' => 'Plumbing repair',
                'plumbing-diagnosis' => 'Plumbing diagnosis',
                'plumbing-leak' => 'Leak repair',
                'plumbing-drain' => 'Drain cleaning',
                'plumbing-pipe' => 'Pipe replacement',
                'plumbing-faucet' => 'Faucet repair',
                'plumbing-toilet' => 'Toilet repair',
                'plumbing-water-heater' => 'Water heater repair',
                'plumbing-sump-pump' => 'Sump pump repair',
                'plumbing-pressure' => 'Water pressure service',
            ],
            'Home Cleaning' => [
                'cleaning-deep' => 'Deep home cleaning',
                'cleaning-regular' => 'Regular home cleaning',
                'cleaning-move-in' => 'Move-in cleaning',
                'cleaning-move-out' => 'Move-out cleaning',
                'cleaning-kitchen' => 'Kitchen cleaning',
                'cleaning-bathroom' => 'Bathroom cleaning',
                'cleaning-upholstery' => 'Upholstery cleaning',
                'cleaning-window' => 'Window cleaning',
                'cleaning-carpet' => 'Carpet cleaning',
                'cleaning-post-construction' => 'Post-construction cleaning',
            ],
            'Carpentry' => [
                'carpentry-furniture' => 'Furniture repair',
                'carpentry-door' => 'Door repair',
                'carpentry-cabinet' => 'Cabinet installation',
                'carpentry-shelf' => 'Shelf installation',
                'carpentry-bed' => 'Bed frame repair',
                'carpentry-table' => 'Table repair',
                'carpentry-window' => 'Window frame repair',
                'carpentry-flooring' => 'Wood flooring repair',
                'carpentry-trim' => 'Trim and molding repair',
                'carpentry-custom' => 'Custom woodwork',
            ],
            'Painting' => [
                'painting-interior' => 'Interior painting',
                'painting-exterior' => 'Exterior painting',
                'painting-ceiling' => 'Ceiling painting',
                'painting-wallpaper' => 'Wallpaper installation',
                'painting-wallpaper-removal' => 'Wallpaper removal',
                'painting-touch-up' => 'Paint touch-up',
                'painting-waterproofing' => 'Waterproof coating',
                'painting-stain' => 'Wood staining',
                'painting-cabinet' => 'Cabinet painting',
                'painting-consultation' => 'Color consultation',
            ],
            'Locksmith' => [
                'locksmith-lockout' => 'Emergency lockout service',
                'locksmith-lock-repair' => 'Lock repair',
                'locksmith-lock-installation' => 'Lock installation',
                'locksmith-key-duplication' => 'Key duplication',
                'locksmith-rekeying' => 'Lock rekeying',
                'locksmith-smart-lock' => 'Smart lock setup',
                'locksmith-safe' => 'Safe opening service',
                'locksmith-gate' => 'Gate lock repair',
                'locksmith-car' => 'Car key assistance',
                'locksmith-security' => 'Home security lock assessment',
            ],
            'Landscaping' => [
                'landscaping-lawn' => 'Lawn mowing',
                'landscaping-garden' => 'Garden maintenance',
                'landscaping-tree-trimming' => 'Tree trimming',
                'landscaping-hedge' => 'Hedge trimming',
                'landscaping-planting' => 'Planting service',
                'landscaping-irrigation' => 'Irrigation repair',
                'landscaping-pest-control' => 'Garden pest control',
                'landscaping-cleanup' => 'Yard cleanup',
                'landscaping-mulching' => 'Mulching service',
                'landscaping-design' => 'Landscape design',
            ],
        ];
    }
}
