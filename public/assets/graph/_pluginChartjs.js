(function () {
    if (typeof Chart === 'undefined') {
      console.warn('Can not find Chart object.');
      return;
    }

    Chart.pluginService.register({
        beforeDraw: function (chart) {
            if (chart.config.options.elements.center) {
                var ctx = chart.chart.ctx;
                var centerConfig = chart.config.options.elements.center;
                var fontStyle = centerConfig.fontStyle || 'Arial';
                var txt = centerConfig.text;
                var color = centerConfig.color || '#000';
                var sidePadding = centerConfig.sidePadding || 20;
                var sidePaddingCalculated = (sidePadding/100) * (chart.innerRadius * 2)
                var stringWidth = ctx.measureText(txt).width;
                var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;
                var widthRatio = elementWidth / stringWidth;
                var newFontSize = Math.floor(30 * widthRatio);
                var elementHeight = (chart.innerRadius * 2);
                var fontSizeToUse = Math.min(newFontSize, elementHeight);
                ctx.textAlign = centerConfig.textAlign || 'center';
                ctx.textBaseline = centerConfig.textBaseline || 'middle';
                var centerX = centerConfig.centerX != null ? centerConfig.centerX : ((chart.chartArea.left + chart.chartArea.right) / 2);
                var centerY = centerConfig.centerY != null ? centerConfig.centerY : ((chart.chartArea.top + chart.chartArea.bottom) / 2);
                ctx.font = centerConfig.fontSize != null ? centerConfig.fontSize + " " + fontStyle : "10px " + fontStyle;
                ctx.fillStyle = color;
                ctx.fillText(txt, centerX, centerY);
            }
        },
        beforeRender: function (chart) {
            if (chart.config.options.showAllTooltips) {
                chart.pluginTooltips = [];
                chart.config.data.datasets.forEach(function (dataset, i) {
                    chart.getDatasetMeta(i).data.forEach(function (sector, j) {
                        chart.pluginTooltips.push(new Chart.Tooltip({
                            _chart: chart.chart,
                            _chartInstance: chart,
                            _data: chart.data,
                            _options: chart.options.tooltips,
                            _active: [sector]
                        }, chart));
                    });
                });
                chart.options.tooltips.enabled = false;
            }
        },
        afterDraw: function (chart, easing) {
            if (chart.config.options.showAllTooltips) {
                if (!chart.allTooltipsOnce) {
                    if (easing !== 1)
                        return;
                    chart.allTooltipsOnce = true;
                }
                chart.options.tooltips.enabled = true;
                Chart.helpers.each(chart.pluginTooltips, function (tooltip) {
                    tooltip.initialize();
                    tooltip.update();
                    tooltip.pivot();
                    tooltip.transition(easing).draw();
                });
                chart.options.tooltips.enabled = false;
            }
        },
        afterDatasetsDraw: function(chart) {
            if (chart.config.options.showTooltips) {
                var ctx = chart.ctx;
                chart.data.datasets.forEach(function(dataset, i) {
                    var meta = chart.getDatasetMeta(i);
                    if (!meta.hidden) {
                        meta.data.forEach(function(element, index) {
                            ctx.fillStyle = 'rgb(0, 0, 0)';
                            var fontSize = chart.config.options.showTooltips.fontSize || 16;
                            var fontStyle = 'normal';
                            var fontFamily = 'Tahoma';
                            var typeValue = chart.config.options.showTooltips.typeValue || '';
                            ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);
                            dataset.data[index] += '';
                            x = dataset.data[index].split('.');
                            x1 = x[0];
                            x2 = x.length > 1 ? '.' + x[1] : '';
                            var rgx = /(\d+)(\d{3})/;
                            while (rgx.test(x1)) {
                                x1 = x1.replace(rgx, '$1' + ',' + '$2');
                            }
                            dataset.data[index] = x1 + x2;
                            var txt= '';
                            if(chart.config.options.showTooltips.showLabel) {
                                txt = chart.data.labels[index] + ' ';
                            }
                            var val = '';
                            if(chart.config.options.showTooltips.showValue) {
                                val = dataset.data[index].toString();
                            }                        
                            val = val.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,");
                            var dataString = txt + val + typeValue;
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            var padding = chart.config.options.showTooltips.padding || 5;
                            var positionX = chart.config.options.showTooltips.positionX || 0;
                            var positionY = chart.config.options.showTooltips.positionY || 0;
                            var position = element.tooltipPosition();
                            var x = 0;
                            ctx.fillText(dataString, position.x + x + positionX, position.y - (fontSize / 2) - padding - positionY);
                        });
                    }
                });
            }
        }
    });
    
})();