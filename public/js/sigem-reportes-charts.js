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

            renderChart() {
                if (!chartConfig || !this.$refs.canvas) return;

                if (typeof Chart === 'undefined') {
                    setTimeout(() => this.renderChart(), 120);
                    return;
                }

                const ctx = this.$refs.canvas.getContext('2d');
                if (!ctx) return;

                const truncateLabel = (val, maxLen = 25) => {
                    if (typeof val === 'string' && val.length > maxLen) {
                        return val.slice(0, maxLen - 3) + '...';
                    }
                    return val;
                };

                const isHorizontal = chartConfig.options?.indexAxis === 'y';
                const isPieOrDonut = ['pie', 'doughnut'].includes(chartConfig.type);
                const baseScales = chartConfig.options?.scales ? JSON.parse(JSON.stringify(chartConfig.options.scales)) : {};
                const finalScales = {};

                if (!isPieOrDonut) {
                    if (isHorizontal) {
                        finalScales.y = Object.assign({}, baseScales.y || {}, {
                            grid: { display: false },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0,
                                font: { size: 11 },
                                callback: function (val, index) {
                                    const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                    return truncateLabel(raw, 25);
                                }
                            }
                        });
                        finalScales.x = Object.assign({}, baseScales.x || {}, {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 11 }, precision: 0 }
                        });
                    } else {
                        finalScales.x = Object.assign({}, baseScales.x || {}, {
                            grid: { display: false },
                            ticks: {
                                autoSkip: true,
                                maxRotation: 45,
                                minRotation: 0,
                                font: { size: 11 },
                                callback: function (val, index) {
                                    const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                    return truncateLabel(raw, 25);
                                }
                            }
                        });
                        finalScales.y = Object.assign({}, baseScales.y || {}, {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { font: { size: 11 }, precision: 0 }
                        });
                    }
                }

                if (this.chartInstance) {
                    try { this.chartInstance.destroy(); } catch (e) {}
                    this.chartInstance = null;
                }

                try {
                    this.chartInstance = new Chart(ctx, {
                        type: chartConfig.type,
                        data: {
                            labels: chartConfig.labels,
                            datasets: chartConfig.datasets
                        },
                        options: Object.assign({
                            responsive: true,
                            maintainAspectRatio: false,
                            resizeDelay: 150,
                            layout: {
                                padding: { left: 4, right: 8, top: 4, bottom: 4 }
                            },
                            plugins: {
                                legend: {
                                    display: isPieOrDonut,
                                    position: 'bottom',
                                    labels: { boxWidth: 10, font: { size: 11 }, padding: 12 }
                                },
                                tooltip: {
                                    enabled: true,
                                    callbacks: {
                                        title: function (items) {
                                            if (!items.length) return '';
                                            const idx = items[0].dataIndex;
                                            return chartConfig.labels?.[idx] ?? items[0].label ?? '';
                                        }
                                    }
                                }
                            },
                            scales: finalScales
                        }, chartConfig.options || {})
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
