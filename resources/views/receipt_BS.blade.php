@php use Carbon\Carbon; @endphp
@props(['project', 'transaction'])

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 750px;
            margin: auto;
            padding: 40px 30px;
            background-color: #ffffff;
            color: #111;
        }

        .my-input {
            border: none;
            border-bottom: 1px dashed #ccc;
            padding: 4px 6px;
            font-size: 13px;
        }

        .my-label {
            color: #999;
        }

        .border-dashed {
            border: 2px dashed #000;
        }

        .flex {
            display: flex;
        }

        .justify-between {
            justify-content: space-between;
        }

        .items-center {
            align-items: center;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-3 {
            margin-top: 12px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .mt-7 {
            margin-top: 28px;
        }

        .mb-8 {
            margin-bottom: 32px;
        }

        .ml-2 {
            margin-left: 8px;
        }

        .ml-3 {
            margin-left: 12px;
        }

        .ml-4 {
            margin-left: 16px;
        }

        .text-sm {
            font-size: 13px;
        }

        .text-base {
            font-size: 16px;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .w-full {
            width: 100%;
        }

        .w-16 {
            width: 64px;
        }

        .w-24 {
            width: 96px;
        }

        .w-48 {
            width: 192px;
        }

        hr {
            border: none;
            border-top: 1px dashed #999;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .contact-block {
            width: 40%;
            max-height: 200px;
            display: grid;
            word-break: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
            font-size: clamp(10px, 0.55vw + .35rem, 14px);
            line-height: 1.25;
        }
    </style>
</head>

<body>
    <div class="container">
        @for ($i = 0; $i < 2; $i++)
            <div class="flex justify-between">
                <div>
                    {{-- @if (file_exists(public_path($project->logo)))
						<img src="{{ public_path($project->logo) }}" alt="Logo" height="120" width="120">
				@else
						<p>[Logo missing]</p>
				@endif --}}
                    <img src="{{ asset($project->logo) }}" alt="Logo" height="80">
                </div>
                <div class="contact-block">
                    <table>
                        <tbody>
                            <tr>
                                <td class="text-right"><span class="font-bold">Office:</span>
                                    {{ $project->office_display_address }}</td>
                            </tr>
                            <tr>
                                <td class="text-right"><span class="font-bold">Site:</span>
                                    {{ $project->site_display_address }}</td>
                            </tr>
                            @if ($project->email)
                                <tr>
                                    <td class="text-right"><span class="font-bold">Email:</span> {{ $project->email }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                {{--                <div style="width: 320px;"> --}}
                {{--                    <table> --}}
                {{--                        <tbody> --}}
                {{--                            <tr> --}}
                {{--                                <td class="text-sm text-right"> --}}
                {{--                                    <span class="font-bold">Office:</span> {{ $project->office_display_address }} --}}
                {{--                                </td> --}}
                {{--                            </tr> --}}
                {{--                            <tr> --}}
                {{--                                <td class="text-sm text-right"> --}}
                {{--                                    <span class="font-bold">Site:</span> {{ $project->site_display_address }} --}}
                {{--                                </td> --}}
                {{--                            </tr> --}}
                {{--                            @if ($project->email) --}}
                {{--                                <tr> --}}
                {{--                                    <td class="text-sm text-right"> --}}
                {{--                                        <span class="font-bold">Email:</span> {{ $project->email }} --}}
                {{--                                    </td> --}}
                {{--                                </tr> --}}
                {{--                            @endif --}}
                {{--                        </tbody> --}}
                {{--                    </table> --}}
                {{--                </div> --}}
            </div>

            <div class="mt-4">
                <div class="flex justify-between items-center">
                    <div>
                        <label class="text-sm">Receipt Number:</label>
                        <input class="my-input w-24 text-center" value="{{ $transaction->receipt_number }}" disabled>
                    </div>
                    <div>
                        <label class="text-sm">Date:</label>
                        <input value="{{ Carbon::parse($transaction->receipt_date)->format('d-m-Y') }}"
                            class="my-input w-24 text-center" disabled>
                    </div>
                </div>

                <div class="flex mt-2 items-center">
                    <label class="text-sm">Name:</label>
                    <input type="text" value="{{ $transaction->customer->name }}" class="my-input w-full ml-2"
                        disabled>
                </div>

                <div class="text-sm mt-2">
                    deposits below amount for {{ ucwords($transaction->unit->type) }} Number:
                    <input value="{{ $transaction->unit->unit_no }}" class="my-input text-center"
                        disabled>
                    at <strong>"{{ $project->name }}"</strong>, accepting all the conditions by the developers for
                    becoming a member.
                </div>

                <div class="flex mt-2 items-center">
                    <label class="text-sm">Bank:</label>
                    <input type="text" value="{{ $transaction->bank_name }}" class="my-input ml-2" disabled>
                    <label class="text-sm ml-3">Branch:</label>
                    <input value="{{ $transaction->bank_branch }}" class="my-input ml-2" disabled>
                </div>

                <div class="flex mt-2 items-center">
                    <label class="text-sm" style="width: 47%;">
                        <span class="{{ $transaction->payment_type === 'cheque' ? '' : 'my-label' }}">Cheque No.</span>
                        <span class="{{ $transaction->payment_type === 'bank_draft' ? '' : 'my-label' }}">/Draft
                            No.</span>
                        <span class="{{ $transaction->payment_type === 'rtgs' ? '' : 'my-label' }}">/RTGS No.</span>
                        <span class="{{ $transaction->payment_type === 'neft' ? '' : 'my-label' }}">/NEFT No.</span>
                        <span class="{{ $transaction->payment_type === 'imps' ? '' : 'my-label' }}">/IMPS</span>
                        <span class="{{ $transaction->payment_type === 'cash' ? '' : 'my-label' }}">/Cash</span>:
                    </label>
                    <input value="{{ $transaction->payment_reference }}" class="my-input text-center" disabled>
                    <label class="text-sm ml-4 text-left">Date:</label>
                    <input value="{{ Carbon::parse($transaction->payment_date)->format('d-m-Y') }}"
                        class="my-input w-[80px] text-center" disabled>
                </div>

                <div class="flex mt-2">
                    <label class="text-sm">Rupees:</label>
                    <input type="text" class="my-input w-full ml-2"
                        value="{{ ucwords($transaction->token_amount_in_words) }}" disabled>
                </div>

                <div class="flex mt-2 items-center">
                    <label class="text-base font-bold mt-4">Amount:</label>
                    <input value="₹{{ $transaction->transaction_amount }}"
                        class="my-input border-dashed ml-4 w-48 text-center h-8" disabled>
                </div>

                <div class="flex justify-between">
                    <div class="mt-7 text-sm">
                        @if ($transaction->payment_type === 'Cheque')
                            <p class="font-bold">Note: The receipt will be valid only after realization of cheque.</p>
                        @endif
                        <p class="mt-3">Subject to {{ $project->jurisdiction }} Jurisdiction</p>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                        <p style="font-size: 14px; font-weight: bold;">For, {{ $project->name }}</p>
                        <div
                            style="width: 60px; height: 64px; margin-bottom: 20px; border: 2px dashed black;">
                        </div>
                    </div>

                </div>
            </div>

            @if ($i == 0)
                <hr class="mb-8">
            @endif
        @endfor
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const box = document.querySelector('.contact-block');
            const maxH = 200; // px
            const minFS = 8; // px
            let size = parseFloat(getComputedStyle(box).fontSize);

            while (box.scrollHeight > maxH && size > minFS) {
                size -= .5; // step down
                box.style.fontSize = size + 'px';
            }
        });
    </script>
</body>

</html>
