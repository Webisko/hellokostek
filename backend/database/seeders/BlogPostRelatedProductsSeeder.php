<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostRelatedProductsSeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'czy-grzyby-witalne-mozna-przedawkowac-dawkowanie-grzybow-witalnych-w-praktyce' => ['reishi-czerwone', 'chaga'],
            'co-jest-najwieksza-inwestycja-w-twoje-zdrowie' => ['mlody-zielony-jeczmien-surowy-sproszkowany-sok-100g'],
            'jak-przetrwac-zime-w-dobrym-zdrowiu-bez-depresyjnego-nastroju-i-nadmiernego-wychlodzenia' => ['reishi-czerwone', 'chaga', 'cordyceps-sinensis'],
            'autofagia-sekret-dlugowiecznosci-japonskiego-biologa' => ['reishi-czerwone', 'soplowka-jezowata-owocnik'],
            'chcesz-mniej-meczyc-sie-i-zwiekszyc-osiagi-sportowe-odkryj-te-2-grzyby' => ['cordyceps-sinensis', 'reishi-czerwone'],
            'tego-nie-powie-ci-lekarz-czyli-o-alkalizacji-i-nawodnieniu' => ['mlody-zielony-jeczmien-surowy-sproszkowany-sok-100g', 'chaga'],
            'ekstrakty-z-grzybow-jaka-jest-roznica-miedzy-proszkiem-a-plynem' => ['reishi-czerwone', 'chaga', 'soplowka-jezowata-ekstrakt-z-grzybni-50g'],
            'cordyceps-w-tradycyjnej-medycynie-chinskiej' => ['soplowka-jezowata-ekstrakt-z-grzybni-50g', 'soplowka-jezowata-owocnik'],
            'czy-grzyby-lecza-alergie' => ['wrosniak-roznobarwny-ekstrakt-50g', 'chaga'],
            'jak-jesc-grzyby-zeby-leczyly-a-nie-szkodzily' => ['reishi-czerwone', 'chaga', 'soplowka-jezowata-owocnik'],
            'jak-naturalnie-podniesc-poziom-energii-witalnej-moja-historia' => ['cordyceps-sinensis', 'reishi-czerwone'],
            'grzyby-zrodlo-aminokwasow-i-swietny-zamiennik-miesa' => ['chaga', 'wrosniak-roznobarwny-ekstrakt-50g'],
            'muchomor-czerwony-i-inne-grzyby-w-kosmetyce' => ['tremella-ekstrakt-50g', 'reishi-czerwone'],
            'jak-system-niszczy-nasze-zdrowie-i-jak-sie-przed-tym-bronic' => ['mlody-zielony-jeczmien-surowy-sproszkowany-sok-100g', 'chaga', 'wrosniak-roznobarwny-ekstrakt-50g'],
        ];

        foreach ($mapping as $slug => $products) {
            $post = BlogPost::where('slug', $slug)->first();
            if ($post) {
                $metadata = $post->metadata ?? [];
                $metadata['related_products'] = $products;
                $post->metadata = $metadata;
                $post->save();
            }
        }
    }
}
