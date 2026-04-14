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
    <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <span class="text-3xl">&#128293;</span>
                <h1 class="text-2xl font-bold tracking-tight text-white">CodeBurn</h1>
                <span class="text-sm text-gray-500">Token Cost Dashboard</span>
            </div>
            <div class="text-xs text-gray-600">
                @if(!empty($data['generated']))
                    Updated: {{ \Carbon\Carbon::parse($data['generated'])->format('M d, Y H:i') }} UTC
                @endif
            </div>
        </div>

        @if(empty($data['periods']))
            <div class="rounded-xl bg-gray-900 border border-gray-800 p-12 text-center">
                <p class="text-gray-400 text-lg">No data available. Make sure Claude Code sessions exist.</p>
            </div>
        @else
            {{-- Period Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                @foreach($data['periods'] as $periodName => $period)
                    <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $periodName }}</div>
                        <div class="text-3xl font-bold text-white mb-3">
                            ${{ number_format($period['summary']['Cost (USD)'] ?? 0, 2) }}
                        </div>
                        <div class="flex gap-4 text-xs text-gray-400">
                            <span>{{ $period['summary']['API Calls'] ?? 0 }} calls</span>
                            <span>{{ $period['summary']['Sessions'] ?? 0 }} sessions</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Daily Cost Chart (30 Days) --}}
            @php
                $monthlyData = $data['periods']['30 Days'] ?? [];
                $dailyData = $monthlyData['daily'] ?? [];
            @endphp
            @if(count($dailyData) > 0)
                <div class="rounded-xl bg-gray-900 border border-gray-800 p-6 mb-8">
                    <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Daily Cost (30 Days)</h2>
                    <div class="h-64">
                        <canvas id="dailyCostChart"></canvas>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                {{-- Activity Breakdown (30 Days) --}}
                @php $activities = $monthlyData['activity'] ?? []; @endphp
                @if(count($activities) > 0)
                    <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Activity Breakdown</h2>
                        <div class="space-y-3">
                            @php $maxActivityCost = max(array_column($activities, 'Cost (USD)')) ?: 1; @endphp
                            @foreach($activities as $activity)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300">{{ $activity['Activity'] }}</span>
                                        <span class="text-gray-400">
                                            ${{ number_format($activity['Cost (USD)'], 2) }}
                                            <span class="text-gray-600 ml-1">{{ $activity['Turns'] ?? 0 }} turns</span>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-2">
                                        <div class="bg-orange-500 h-2 rounded-full transition-all" style="width: {{ ($activity['Cost (USD)'] / $maxActivityCost) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Model Breakdown (30 Days) --}}
                @php $models = $monthlyData['models'] ?? []; @endphp
                @if(count($models) > 0)
                    <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Cost by Model</h2>
                        <div class="space-y-3">
                            @php $maxModelCost = max(array_column($models, 'Cost (USD)')) ?: 1; @endphp
                            @foreach($models as $model)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300">{{ $model['Model'] }}</span>
                                        <span class="text-gray-400">
                                            ${{ number_format($model['Cost (USD)'], 2) }}
                                            <span class="text-gray-600 ml-1">{{ number_format($model['Input Tokens'] ?? 0) }} in / {{ number_format($model['Output Tokens'] ?? 0) }} out</span>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-2">
                                        <div class="bg-violet-500 h-2 rounded-full transition-all" style="width: {{ ($model['Cost (USD)'] / $maxModelCost) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
                {{-- Projects --}}
                @php $projects = $data['projects'] ?? []; @endphp
                @if(count($projects) > 0)
                    <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
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
                                <tbody class="divide-y divide-gray-800">
                                    @foreach($projects as $project)
                                        <tr>
                                            <td class="py-2 text-gray-300 font-mono text-xs">{{ $project['Project'] }}</td>
                                            <td class="py-2 text-right text-gray-400">${{ number_format($project['Cost (USD)'], 2) }}</td>
                                            <td class="py-2 text-right text-gray-500">{{ $project['API Calls'] }}</td>
                                            <td class="py-2 text-right text-gray-500">{{ $project['Sessions'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Tools --}}
                @php $tools = $data['tools'] ?? []; @endphp
                @if(count($tools) > 0)
                    <div class="rounded-xl bg-gray-900 border border-gray-800 p-6">
                        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Tool Usage</h2>
                        <div class="space-y-2">
                            @php $maxToolCalls = max(array_column($tools, 'Calls')) ?: 1; @endphp
                            @foreach($tools as $tool)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-300 font-mono text-xs">{{ $tool['Tool'] }}</span>
                                        <span class="text-gray-500">{{ $tool['Calls'] }} calls</span>
                                    </div>
                                    <div class="w-full bg-gray-800 rounded-full h-1.5">
                                        <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: {{ ($tool['Calls'] / $maxToolCalls) * 100 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="text-center text-xs text-gray-700 py-4">
            Powered by <a href="https://github.com/agentSeal/codeburn" class="text-gray-500 hover:text-gray-400" target="_blank">CodeBurn</a>
        </div>
    </div>

    @if(!empty($dailyData))
        <script>
            const dailyData = @json($dailyData);
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
        </script>
    @endif
</body>
</html>
