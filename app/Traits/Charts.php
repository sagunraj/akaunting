<?php

namespace App\Traits;

use Akaunting\Apexcharts\Chart;
use Balping\JsonRaw\Raw;

trait Charts
{
    public $bar = [
        'colors' => [],
        'labels' => [],
        'values' => [],
    ];

    public $donut = [
        'colors' => [],
        'labels' => [],
        'values' => [],
    ];

    public function addToDonut($color, $label, $value)
    {
        $this->donut['colors'][] = $color;
        $this->donut['labels'][] = $label;
        $this->donut['values'][] = (int) $value;
    }

    public function addMoneyToDonut($color, $amount, $description = '')
    {
        $label = money($amount, setting('default.currency'), true)->formatForHumans();

        if (!empty($description)) {
            $label .= ' - ' . $description;
        }

        $this->addToDonut($color, $label, $amount);
    }

    public function getDonutChart($name, $width = '100%', $height = 300, $limit = 10)
    {
        // Show donut prorated if there is no value
        if (array_sum($this->donut['values']) == 0) {
            foreach ($this->donut['values'] as $key => $value) {
                $this->donut['values'][$key] = 1;
            }
        }

        // Get 6 categories by amount
        $colors = $labels = [];
        $values = collect($this->donut['values'])->sort()->reverse()->take($limit)->all();

        foreach ($values as $id => $val) {
            $colors[$id] = $this->donut['colors'][$id];
            $labels[$id] = $this->donut['labels'][$id];
        }

        $chart = new Chart();

        $chart->setType('donut')
            ->setWidth($width)
            ->setHeight($height)
            ->setLabels(array_values($labels))
            ->setColors(array_values($colors))
            ->setDataset($name, 'donut', array_values($values));

        return $chart;
    }

    public function addToBar($color, $label, $value)
    {
        $this->bar['colors'][] = $color;
        $this->bar['labels'][] = $label;
        $this->bar['values'][] = (int) $value;
    }

    public function getBarChart($name, $width = '100%', $height = 160)
    {
        $chart = new Chart();

        $chart->setType('bar')
            ->setWidth($width)
            ->setHeight($height)
            ->setLabels(array_values($this->bar['labels']))
            ->setColors($this->bar['colors']);

        foreach ($this->bar['values'] as $key => $value) {
            $chart->setDataset($this->bar['labels'][$key], 'bar', $value);
        }

        return $chart;
    }

    public function getFormatLabel($type = 'money', $position = null)
    {
        $label = '';
        $decimal_mark = str_replace("'", "\\'", config('money.' . setting('default.currency') . '.decimal_mark'));
        $thousands_separator = str_replace("'", "\\'", config('money.' . setting('default.currency') . '.thousands_separator'));
        $symbol = str_replace("'", "\\'", config('money.' . setting('default.currency') . '.symbol'));
        $symbol_first = str_replace("'", "\\'", config('money.' . setting('default.currency') . '.symbol_first'));
        $precision = str_replace("'", "\\'", config('money.' . setting('default.currency') . '.precision'));
        $percent_position = $position ?: setting('localisation.percent_position');

        switch ($type) {
            case 'percent':
                $label = new Raw("function(value) {
                    " . ($percent_position == 'right' ? "return value + '%';" : "return '%' + value;") . "
                }");
                break;
            default:
                $label = new Raw("function(value) {
                    const moneySettings = {
                        decimal: '" . $decimal_mark . "',
                        thousands: '". $thousands_separator . "',
                        symbol: '" . $symbol . "',
                        isPrefix: '" . $symbol_first . "',
                        precision: '" . $precision . "',
                    };

                    const formattedCurrency = function (input, opt = moneySettings) {
                        if (typeof input === 'number') {
                            input = input.toFixed(fixed(opt.precision))
                        }

                        function fixed (precision) {
                            return Math.max(0, Math.min(precision, 20));
                        };

                        function toStr(value) {
                            return value ? value.toString() : '';
                        };

                        function numbersToCurrency(numbers, precision) {
                            var exp = Math.pow(10, precision);
                            var float = parseFloat(numbers) / exp;

                            return float.toFixed(fixed(precision));
                        };

                        function joinIntegerAndDecimal (integer, decimal, separator) {
                            return decimal ? integer + separator + decimal : integer;
                        };

                        if (typeof input === 'number') {
                            input = input.toFixed(fixed(opt.precision));
                        };

                        var negative = input.indexOf('-') >= 0 ? '-' : '';
                        var numbers = toStr(input).replace(/\D+/g, '') || '0';
                        var currency = numbersToCurrency(numbers, opt.precision);
                        var parts = toStr(currency).split('.');
                        var integer = parts[0].replace(/(\d)(?=(?:\d{3})+\b)/gm,  ('$1' + opt.thousands));
                        var decimal = parts[1];

                        if (opt.isPrefix == 1) {
                            return opt.symbol + negative + joinIntegerAndDecimal(integer, decimal, opt.decimal);
                        }

                        return negative + joinIntegerAndDecimal(integer, decimal, opt.decimal) + opt.symbol;
                    };

                    return formattedCurrency(value, moneySettings);
                }");
        }

        return $label;
    }
}
