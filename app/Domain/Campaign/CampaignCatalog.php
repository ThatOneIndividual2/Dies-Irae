<?php

namespace App\Domain\Campaign;

final class CampaignCatalog
{
    public static function definition(string $key): array
    {
        $all = self::all();
        if (!isset($all[$key])) {
            throw new \InvalidArgumentException("Unknown campaign beat: {$key}");
        }

        return $all[$key];
    }

    public static function all(): array
    {
        return [
            BeatKey::PLAGUE_APPEARS => [
                'title' => 'Plague in Aix',
                'body' => 'Riders from Aix say the swellings have come. Carts of the dead clog the Durance road. Your physicians wait on your word.',
                'options' => [
                    'close_roads' => 'Close the roads into Salon',
                    'send_physicians' => 'Send physicians and alms',
                    'ignore' => 'Leave it to the town',
                ],
            ],
            BeatKey::REFUGEES_ARRIVE => [
                'title' => 'Refugees at the gate',
                'body' => 'A column from the coast and from Aix camps beneath the walls of Salon. They bring mouths, labor, and sickness.',
                'options' => [
                    'admit' => 'Admit them into Salon',
                    'turn_away' => 'Turn them away',
                    'send_to_monastery' => 'Send them to Saint-Michel',
                ],
            ],
            BeatKey::NOBLE_REFUSES_AID => [
                'title' => 'Pélissanne withholds aid',
                'body' => 'Gui de Pélissanne refuses grain and levy. He says his own village must eat first.',
                'options' => [
                    'demand_compliance' => 'Demand the contract',
                    'let_it_go' => 'Let it go this season',
                    'seize_stores' => 'Seize his stores',
                ],
            ],
            BeatKey::MONASTERY_REQUESTS_RESOURCES => [
                'title' => 'The abbey asks for stores',
                'body' => 'Abbot Étienne writes that Saint-Michel can house the sick if Salon sends grain and coin. He also asks you to hear a mass for the dead.',
                'options' => [
                    'grant' => 'Grant grain and coin',
                    'refuse' => 'Refuse',
                    'ask_prayers' => 'Offer prayers, not stores',
                ],
            ],
            BeatKey::RUMORS_OF_HERESY => [
                'title' => 'Rumors of heresy',
                'body' => 'Parish talk in Salon names flagellants and a night sermon in the olive groves. Father Bertrand wants leave to question them.',
                'options' => [
                    'investigate' => 'Investigate quietly',
                    'ask_bishop' => 'Refer the matter to the bishop',
                    'ignore' => 'Ignore village gossip',
                ],
            ],
            BeatKey::CULT_DISCOVERY => [
                'title' => 'A cult is found',
                'body' => 'In a cellar near Aix they found a circle of ash and a stolen pyx. The cell names itself the Brothers of the Open Grave.',
                'options' => [
                    'arrest' => 'Arrest the cell',
                    'burn' => 'Burn the cellar and the books',
                    'conceal' => 'Conceal the find',
                ],
            ],
            BeatKey::DEMONIC_MANIFESTATION => [
                'title' => 'A minor manifestation',
                'body' => 'At dusk the well at Aix boiled black. Witnesses swear a shape walked the charnel pit and the bells rang without hands. The veil is thin.',
                'options' => [
                    'call_clergy' => 'Call the bishop and abbot',
                    'send_soldiers' => 'Send soldiers to hold the square',
                    'evacuate' => 'Evacuate the living',
                ],
            ],
            BeatKey::CLERGY_RESPONSE => [
                'title' => 'The Church answers',
                'body' => 'Bishop Jacques and Abbot Étienne stand ready. One would exorcise the well. One would walk a procession. Avignon can be told.',
                'options' => [
                    'exorcism' => 'Permit an exorcism',
                    'procession' => 'Order a public procession',
                    'write_avignon' => 'Write to Avignon',
                ],
            ],
            BeatKey::MILITARY_RESPONSE => [
                'title' => 'Steel against the pit',
                'body' => 'Your captains ask whether to strike the manifestation, garrison Salon, or hunt remaining cultists in the groves.',
                'options' => [
                    'attack_manifestation' => 'Attack the manifestation',
                    'garrison_capital' => 'Garrison Salon',
                    'hunt_cult' => 'Hunt the cult remnants',
                ],
            ],
            BeatKey::AFTERMATH => [
                'title' => 'Aftermath',
                'body' => 'The clerks count the dead. The bishop counts sins. The land is not what it was in October.',
                'options' => [
                    'record_chronicle' => 'Record the chronicle and endure',
                ],
            ],
        ];
    }
}
