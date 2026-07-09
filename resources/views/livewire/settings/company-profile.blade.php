<div>
    <x-ui.save-alert :message="$saveMessage" :status="$saveStatus" />

    <form wire:submit="save">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="font-semibold text-slate-900">Organization settings</h3>
                <p class="mt-0.5 text-sm text-slate-500">Your company profile and domain auto-join rules.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-1/3 px-6 py-3">Setting</th>
                            <th class="px-6 py-3">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Organization name</td>
                            <td class="px-6 py-4">
                                <input wire:model="name" type="text" required
                                    class="w-full max-w-md rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Industry</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $company->industry->label() }}
                                <span class="ml-2 text-xs text-slate-400">(set at registration)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">GSTIN</td>
                            <td class="px-6 py-4">
                                <input wire:model="gstin" type="text" placeholder="22AAAAA0000A1Z5"
                                    class="w-full max-w-md rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Currency</td>
                            <td class="px-6 py-4">
                                <select wire:model="currency"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                    <option value="INR">INR — Indian Rupee</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Financial year starts</td>
                            <td class="px-6 py-4">
                                <select wire:model="fy_start_month"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                                    @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $i => $month)
                                        <option value="{{ $i + 1 }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Work email domain</td>
                            <td class="px-6 py-4">
                                <div class="flex max-w-md rounded-lg border border-slate-200 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20">
                                    <span class="flex items-center rounded-l-lg bg-slate-50 px-3 text-sm text-slate-500">@</span>
                                    <input wire:model="domain" type="text" placeholder="xyz.com"
                                        class="w-full rounded-r-lg border-0 px-3 py-2 text-sm focus:outline-none focus:ring-0">
                                </div>
                                @error('domain') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-xs text-slate-400">Colleagues with this domain can join automatically.</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Domain auto-join</td>
                            <td class="px-6 py-4">
                                <label class="inline-flex items-center gap-2 text-slate-700">
                                    <input wire:model="domain_auto_join" type="checkbox"
                                        class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>Allow colleagues with this domain to join as Employee</span>
                                </label>
                            </td>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Trial status</td>
                            <td class="px-6 py-4 text-slate-600">
                                @if($company->onTrial())
                                    Active until {{ $company->trial_ends_at->format('M j, Y') }}
                                @else
                                    {{ ucfirst($company->status->value) }}
                                @endif
                            </td>
                        </tr>
                        <tr class="bg-slate-50/50">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">Team</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('settings.team') }}" wire:navigate
                                    class="font-semibold text-emerald-600 hover:underline">Manage team members →</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end border-t border-slate-200 px-6 py-4">
                <x-ui.submit-button label="Save changes" loading-label="Saving…" />
            </div>
        </div>
    </form>
</div>
