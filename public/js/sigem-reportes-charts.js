/**
 * SIGEM — Componente Alpine.js optimizado para Chart.js en Nuevos Reportes.
 * Incluye lazy loading (IntersectionObserver + requestIdleCallback),
 * throttle en resize (resizeDelay: 150) y limpieza segura (destroy).
 */
if (!window.sigemChartComponent) {
    window.sigemChartComponent = function (chartConfig) {
        return {
            chartInstance: null,
            observer: null,
            idleHandle: null,
            initialized: false,

            initChart() {
                if (!chartConfig || !this.$refs.canvas) return;

                const scheduleRender = () => {
                    if (this.initialized) return;
                    const run = () => {
                        if (!this.$refs.canvas || !document.body.contains(this.$refs.canvas)) return;
                        this.initialized = true;
                        this.renderChart();
                    };

                    if (typeof window.requestIdleCallback === 'function') {
                        this.idleHandle = window.requestIdleCallback(run, { timeout: 250 });
                    } else {
                        this.idleHandle = setTimeout(run, 16);
                    }
                };

                if ('IntersectionObserver' in window) {
                    this.observer = new IntersectionObserver((entries) => {
                        for (const entry of entries) {
                            if (entry.isIntersecting) {
                                if (this.observer) {
                                    this.observer.disconnect();
                                    this.observer = null;
                                }
                                scheduleRender();
                                break;
                            }
                        }
                    }, { rootMargin: '120px' });

                    this.$nextTick(() => {
                        if (this.$refs.canvas && this.observer) {
                            this.observer.observe(this.$refs.canvas);
                        }
                    });
                } else {
                    this.$nextTick(() => scheduleRender());
                }
            },

            ensureRendered() {
                if (!chartConfig || !this.$refs.canvas) return;
                if (this.chartInstance) {
                    try { this.chartInstance.resize(); } catch (e) {}
                    return;
                }
                if (this.$refs.canvas.offsetWidth === 0) {
                    setTimeout(() => this.ensureRendered(), 60);
                    return;
                }
                this.initialized = true;
                this.renderChart();
            },

            renderChart() {
                if (!chartConfig || !this.$refs.canvas) return;

                if (this.$refs.canvas.offsetWidth === 0) {
                    this.initialized = false;
                    setTimeout(() => this.ensureRendered(), 80);
                    return;
                }

                if (typeof Chart === 'undefined') {
                    setTimeout(() => this.renderChart(), 120);
                    return;
                }

                const ctx = this.$refs.canvas.getContext('2d');
                if (!ctx) return;

                const truncateLabel = (val, maxLen = 38) => {
                    if (typeof val === 'string' && val.length > maxLen) {
                        return val.slice(0, maxLen - 3) + '...';
                    }
                    return val;
                };

                const isHorizontal = chartConfig.options?.indexAxis === 'y';
                const isPieOrDonut = ['pie', 'doughnut'].includes(chartConfig.type);
                const labelsCount = Array.isArray(chartConfig.labels) ? chartConfig.labels.length : 0;

                // Altura dinámica sin scroll interno: max(350, cantidad_items × 40 + 120) en barras horizontales
                const wrap = this.$refs.canvas.parentElement;
                if (wrap) {
                    wrap.style.overflow = 'visible';
                    if (isHorizontal && labelsCount > 0) {
                        const dynamicHeight = Math.max(350, labelsCount * 40 + 120);
                        wrap.style.minHeight = dynamicHeight + 'px';
                        wrap.style.height = dynamicHeight + 'px';
                    }
                }

                const baseScales = chartConfig.options?.scales ? JSON.parse(JSON.stringify(chartConfig.options.scales)) : {};
                const finalScales = {};

                if (!isPieOrDonut) {
                    if (isHorizontal) {
                        finalScales.y = Object.assign({}, baseScales.y || {}, {
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0,
                                font: { size: 12 },
                                callback: function (val, index) {
                                    const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                    return truncateLabel(raw, 38);
                                }
                            }
                        });
                        finalScales.x = Object.assign({}, baseScales.x || {}, {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 12 }, precision: 0 }
                        });
                    } else {
                        finalScales.x = Object.assign({}, baseScales.x || {}, {
                            grid: { display: false },
                            ticks: {
                                autoSkip: true,
                                maxRotation: 35,
                                minRotation: 0,
                                font: { size: 12 },
                                callback: function (val, index) {
                                    const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                    return truncateLabel(raw, 30);
                                }
                            }
                        });
                        finalScales.y = Object.assign({}, baseScales.y || {}, {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 12 }, precision: 0 }
                        });
                    }
                }

                if (this.chartInstance) {
                    try { this.chartInstance.destroy(); } catch (e) {}
                    this.chartInstance = null;
                }

                const rawOptions = Object.assign({}, chartConfig.options || {});
                const incomingTitle = chartConfig.options?.plugins?.title || null;
                const chartTitleText = incomingTitle?.text || chartConfig.chart_title || chartConfig.datasets?.[0]?.label || '';
                const chartUnit = chartConfig.chart_unit || 'registros';
                delete rawOptions.plugins;
                delete rawOptions.scales;

                const formatValueWithUnit = (val) => {
                    const num = Number(val ?? 0);
                    if (chartUnit === 'MXN') {
                        return '$' + num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' MXN';
                    }
                    const singularMap = {
                        equipos: 'equipo',
                        mantenimientos: 'mantenimiento',
                        movimientos: 'movimiento',
                        cambios: 'cambio',
                        acciones: 'acción',
                        registros: 'registro'
                    };
                    const unitLabel = num === 1 ? (singularMap[chartUnit] || chartUnit) : chartUnit;
                    return `${num} ${unitLabel}`;
                };

                // Opción C: en gráficas de barras, mostrar indicador mínimo visible y etiqueta "0"
                // para departamentos/elementos con 0 registros + valores legibles al lado de cada barra.
                const sigemZeroBarPlugin = {
                    id: 'sigemZeroBarAndValueLabels',
                    afterDatasetsDraw(chart) {
                        if (chart.config.type !== 'bar') return;
                        const { ctx: c, data, scales } = chart;
                        const dataset = data.datasets?.[0];
                        const meta = chart.getDatasetMeta(0);
                        if (!dataset || !meta || !meta.data) return;

                        const horizontal = chart.options?.indexAxis === 'y';
                        c.save();
                        c.font = '600 11.5px Inter, system-ui, -apple-system, sans-serif';
                        c.textBaseline = 'middle';

                        meta.data.forEach((barEl, idx) => {
                            const val = Number(dataset.data?.[idx] ?? 0);
                            if (horizontal) {
                                const xBase = scales.x ? scales.x.getPixelForValue(0) : barEl.base;
                                if (val === 0) {
                                    const barH = Math.max(8, Math.min(18, (barEl.height || 14)));
                                    c.fillStyle = '#cbd5e1';
                                    c.fillRect(xBase, barEl.y - barH / 2, 4, barH);
                                    c.fillStyle = '#64748b';
                                    c.textAlign = 'left';
                                    c.fillText('0', xBase + 9, barEl.y);
                                } else {
                                    c.fillStyle = '#334155';
                                    c.textAlign = 'left';
                                    c.fillText(String(val), barEl.x + 6, barEl.y);
                                }
                            } else {
                                const yBase = scales.y ? scales.y.getPixelForValue(0) : barEl.base;
                                c.textAlign = 'center';
                                if (val === 0) {
                                    const barW = Math.max(10, Math.min(28, (barEl.width || 18)));
                                    c.fillStyle = '#cbd5e1';
                                    c.fillRect(barEl.x - barW / 2, yBase - 4, barW, 4);
                                    c.fillStyle = '#64748b';
                                    c.fillText('0', barEl.x, yBase - 12);
                                } else {
                                    c.fillStyle = '#334155';
                                    c.fillText(String(val), barEl.x, barEl.y - 10);
                                }
                            }
                        });

                        c.restore();
                    }
                };

                try {
                    this.chartInstance = new Chart(ctx, {
                        type: chartConfig.type,
                        data: {
                            labels: chartConfig.labels,
                            datasets: chartConfig.datasets
                        },
                        plugins: [sigemZeroBarPlugin],
                        options: Object.assign({
                            responsive: true,
                            maintainAspectRatio: false,
                            resizeDelay: 150,
                            layout: {
                                padding: { left: 10, right: isHorizontal ? 34 : 18, top: 8, bottom: 8 }
                            },
                            plugins: {
                                title: Object.assign({
                                    display: Boolean(chartTitleText),
                                    text: chartTitleText,
                                    align: 'start',
                                    color: '#0f172a',
                                    font: { size: 15, weight: '600' },
                                    padding: { top: 4, bottom: 14 }
                                }, incomingTitle || {}),
                                legend: {
                                    display: isPieOrDonut,
                                    position: 'right',
                                    labels: { boxWidth: 14, font: { size: 12.5 }, padding: 14, usePointStyle: true }
                                },
                                tooltip: {
                                    enabled: true,
                                    callbacks: {
                                        title: function (items) {
                                            if (!items.length) return '';
                                            const idx = items[0].dataIndex;
                                            return chartConfig.labels?.[idx] ?? items[0].label ?? '';
                                        },
                                        label: function (context) {
                                            const rawVal = isHorizontal
                                                ? (context.parsed?.x ?? context.raw)
                                                : (isPieOrDonut ? context.raw : (context.parsed?.y ?? context.raw));
                                            return ' ' + formatValueWithUnit(rawVal);
                                        }
                                    }
                                }
                            },
                            scales: finalScales
                        }, rawOptions)
                    });
                } catch (err) {
                    console.warn('Error inicializando gráfica SIGEM:', err);
                }
            },

            destroy() {
                if (this.observer) {
                    this.observer.disconnect();
                    this.observer = null;
                }
                if (this.idleHandle) {
                    if (typeof window.cancelIdleCallback === 'function') {
                        window.cancelIdleCallback(this.idleHandle);
                    } else {
                        clearTimeout(this.idleHandle);
                    }
                    this.idleHandle = null;
                }
                if (this.chartInstance) {
                    try { this.chartInstance.destroy(); } catch (e) {}
                    this.chartInstance = null;
                }
            }
        };
    };
}
