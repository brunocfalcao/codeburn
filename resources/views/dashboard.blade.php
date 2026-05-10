<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeBurn - Token Cost Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen">
    {{-- Loading Modal --}}
    <div id="loading-overlay" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 backdrop-blur-sm transition-opacity duration-300">
        <div class="rounded-2xl bg-gray-900 border border-gray-800 shadow-2xl px-10 py-8 flex flex-col items-center gap-5">
            <div class="relative flex items-center justify-center">
                <svg class="animate-spin h-10 w-10 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
            <div class="text-center">
                <p class="text-white text-lg font-semibold">Running CodeBurn</p>
                <p class="text-gray-400 text-sm mt-1">Crunching your token data...</p>
            </div>
        </div>
    </div>

    <div id="dashboard-content" class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8 opacity-0 transition-opacity duration-300">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <span class="text-3xl">&#128293;</span>
                <h1 class="text-2xl font-bold tracking-tight text-white">CodeBurn</h1>
                <span class="text-sm text-gray-500">Token Cost Dashboard</span>
            </div>
            <div id="updated-at" class="text-xs text-gray-600"></div>
        </div>

        {{-- Empty State --}}
        <div id="empty-state" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-12 text-center">
            <p class="text-gray-400 text-lg">No data available. Make sure Claude Code sessions exist.</p>
        </div>

        {{-- Data Container --}}
        <div id="data-container" class="hidden">
            {{-- Period Summary Cards --}}
            <div id="period-cards" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8"></div>

            {{-- Daily Cost Chart --}}
            <div id="chart-container" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-6 mb-8">
                <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Daily Cost (30 Days)</h2>
                <div class="h-64">
                    <canvas id="dailyCostChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                {{-- Activity Breakdown --}}
                <div id="activity-container" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Activity Breakdown</h2>
                    <div id="activity-list" class="space-y-3"></div>
                </div>

                {{-- Model Breakdown --}}
                <div id="models-container" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Cost by Model</h2>
                    <div id="models-list" class="space-y-3"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                {{-- Projects --}}
                <div id="projects-container" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Projects</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs uppercase tracking-wider">
                                    <th class="text-left pb-3">Project</th>
                                    <th class="text-right pb-3">Cost</th>
                                    <th class="text-right pb-3">Calls</th>
                                    <th class="text-right pb-3">Sessions</th>
                                </tr>
                            </thead>
                            <tbody id="projects-body" class="divide-y divide-gray-800"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Tools --}}
                <div id="tools-container" class="hidden rounded-xl bg-gray-900 border border-gray-800 p-6">
                    <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Tool Usage</h2>
                    <div id="tools-list" class="space-y-2"></div>
                </div>
            </div>
        </div>

        <div class="text-center text-xs text-gray-700 py-4">
            Powered by <a href="https://github.com/agentSeal/codeburn" class="text-gray-500 hover:text-gray-400" target="_blank">CodeBurn</a>
        </div>
    </div>

    <script>
        function numberFormat(n, decimals = 0) {
            return n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function renderBarRow(label, value, max, color, suffix = '') {
            const pct = max > 0 ? (value / max) * 100 : 0;
            return `<div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">${escapeHtml(label)}</span>
                    <span class="text-gray-400">$${numberFormat(value, 2)}${suffix}</span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-2">
                    <div class="${color} h-2 rounded-full transition-all" style="width: ${pct}%"></div>
                </div>
            </div>`;
        }

        async function loadDashboard() {
            try {
                const response = await fetch('{{ route("dashboard.data") }}');
                const data = await response.json();

                const overlay = document.getElementById('loading-overlay');
                const content = document.getElementById('dashboard-content');

                // Updated timestamp
                if (data.generated) {
                    const date = new Date(data.generated);
                    const formatted = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                        + ' ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                    document.getElementById('updated-at').textContent = 'Updated: ' + formatted + ' UTC';
                }

                if (!data.periods || Object.keys(data.periods).length === 0) {
                    document.getElementById('empty-state').classList.remove('hidden');
                } else {
                    document.getElementById('data-container').classList.remove('hidden');

                    // Period cards
                    const cardsHtml = Object.entries(data.periods).map(([name, period]) => `
                        <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">${escapeHtml(name)}</div>
                            <div class="text-3xl font-bold text-white mb-3">$${numberFormat(period.summary?.['Cost (USD)'] ?? 0, 2)}</div>
                            <div class="flex gap-4 text-xs text-gray-400">
                                <span>${period.summary?.['API Calls'] ?? 0} calls</span>
                                <span>${period.summary?.['Sessions'] ?? 0} sessions</span>
                            </div>
                        </div>
                    `).join('');
                    document.getElementById('period-cards').innerHTML = cardsHtml;

                    // Daily chart
                    const monthlyData = data.periods['30 Days'] ?? {};
                    const dailyData = monthlyData.daily ?? [];
                    if (dailyData.length > 0) {
                        document.getElementById('chart-container').classList.remove('hidden');
                        const ctx = document.getElementById('dailyCostChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: dailyData.map(d => {
                                    const date = new Date(d['Date'] + 'T00:00:00');
                                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                                }),
                                datasets: [{
                                    label: 'Cost (USD)',
                                    data: dailyData.map(d => d['Cost (USD)']),
                                    backgroundColor: 'rgba(249, 115, 22, 0.6)',
                                    borderColor: 'rgb(249, 115, 22)',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        callbacks: {
                                            label: (ctx) => `$${ctx.parsed.y.toFixed(2)}`
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: { color: 'rgba(255,255,255,0.05)' },
                                        ticks: { color: '#6b7280', font: { size: 10 } }
                                    },
                                    y: {
                                        grid: { color: 'rgba(255,255,255,0.05)' },
                                        ticks: {
                                            color: '#6b7280',
                                            font: { size: 10 },
                                            callback: (v) => '$' + v.toFixed(2)
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Activity breakdown
                    const activities = monthlyData.activity ?? [];
                    if (activities.length > 0) {
                        document.getElementById('activity-container').classList.remove('hidden');
                        const maxCost = Math.max(...activities.map(a => a['Cost (USD)'])) || 1;
                        document.getElementById('activity-list').innerHTML = activities.map(a =>
                            renderBarRow(
                                a['Activity'],
                                a['Cost (USD)'],
                                maxCost,
                                'bg-orange-500',
                                ` <span class="text-gray-600 ml-1">${a['Turns'] ?? 0} turns</span>`
                            )
                        ).join('');
                    }

                    // Models breakdown
                    const models = monthlyData.models ?? [];
                    if (models.length > 0) {
                        document.getElementById('models-container').classList.remove('hidden');
                        const maxModelCost = Math.max(...models.map(m => m['Cost (USD)'])) || 1;
                        document.getElementById('models-list').innerHTML = models.map(m =>
                            renderBarRow(
                                m['Model'],
                                m['Cost (USD)'],
                                maxModelCost,
                                'bg-violet-500',
                                ` <span class="text-gray-600 ml-1">${numberFormat(m['Input Tokens'] ?? 0)} in / ${numberFormat(m['Output Tokens'] ?? 0)} out</span>`
                            )
                        ).join('');
                    }

                    // Projects
                    const projects = data.projects ?? [];
                    if (projects.length > 0) {
                        document.getElementById('projects-container').classList.remove('hidden');
                        document.getElementById('projects-body').innerHTML = projects.map(p => `
                            <tr>
                                <td class="py-2 text-gray-300 font-mono text-xs">${escapeHtml(p['Project'])}</td>
                                <td class="py-2 text-right text-gray-400">$${numberFormat(p['Cost (USD)'], 2)}</td>
                                <td class="py-2 text-right text-gray-500">${p['API Calls']}</td>
                                <td class="py-2 text-right text-gray-500">${p['Sessions']}</td>
                            </tr>
                        `).join('');
                    }

                    // Tools
                    const tools = data.tools ?? [];
                    if (tools.length > 0) {
                        document.getElementById('tools-container').classList.remove('hidden');
                        const maxCalls = Math.max(...tools.map(t => t['Calls'])) || 1;
                        document.getElementById('tools-list').innerHTML = tools.map(t => `
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-300 font-mono text-xs">${escapeHtml(t['Tool'])}</span>
                                    <span class="text-gray-500">${t['Calls']} calls</span>
                                </div>
                                <div class="w-full bg-gray-800 rounded-full h-1.5">
                                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: ${(t['Calls'] / maxCalls) * 100}%"></div>
                                </div>
                            </div>
                        `).join('');
                    }
                }

                // Fade out overlay, fade in content
                overlay.classList.add('opacity-0');
                content.classList.remove('opacity-0');
                content.classList.add('opacity-100');
                setTimeout(() => overlay.remove(), 300);

            } catch (error) {
                const overlay = document.getElementById('loading-overlay');
                overlay.innerHTML = `
                    <div class="rounded-2xl bg-gray-900 border border-red-800 shadow-2xl px-10 py-8 flex flex-col items-center gap-4">
                        <p class="text-red-400 text-lg font-semibold">Failed to load data</p>
                        <p class="text-gray-400 text-sm">${escapeHtml(error.message)}</p>
                        <button onclick="location.reload()" class="mt-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm rounded-lg transition-colors">Retry</button>
                    </div>
                `;
            }
        }

        loadDashboard();
    </script>
</body>
</html>
