@extends('layouts.auth')

@section('title', '500 - Server Error')

@section('content')
<main class="min-vh-100 d-flex align-items-center justify-content-center py-6" style="background: #f8f9fa;">
    <section class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8 col-12 text-center">

                {{-- Icon --}}
                <div class="position-relative d-inline-flex align-items-center justify-content-center mb-6">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 140px; height: 140px; background: linear-gradient(135deg, rgba(255,193,7,0.15), rgba(255,193,7,0.05)); border: 2px solid rgba(255,193,7,0.2);">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                            stroke="#e6a817" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <path d="M12 9v4M12 17h.01"/>
                        </svg>
                    </div>
                    <div class="position-absolute rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(220,53,69,0.2), rgba(220,53,69,0.1)); border: 2px solid #dc3545; top: -8px; right: -8px; box-shadow: 0 2px 8px rgba(220,53,69,0.2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="#dc3545" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </div>
                </div>

                {{-- Badge --}}
                <div class="d-inline-block mb-3">
                    <span class="badge rounded-pill" style="background: linear-gradient(135deg, rgba(255,193,7,0.15), rgba(255,193,7,0.05)); color: #e6a817; font-size: 12px; padding: 8px 18px; border: 1px solid rgba(255,193,7,0.3); font-weight: 600;">
                        ⚠ Error 500
                    </span>
                </div>

                {{-- Heading --}}
                <h1 class="mb-2" style="font-size: 36px; font-weight: 700; color: #1a1a1a; line-height: 1.2;">Terjadi Kesalahan Server</h1>
                <p class="text-muted mb-5" style="font-size: 16px; line-height: 1.7; color: #666; max-width: 500px; margin-left: auto; margin-right: auto;">
                    Server mengalami masalah internal dan tidak dapat memproses permintaan Anda. Tim teknis kami sudah diberitahu dan akan menangani masalah ini dengan segera.
                </p>

                {{-- Detail Card --}}
                <div class="card card-lg mb-5 text-start" style="border: 1px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
                    <div class="card-body" style="padding: 24px;">
                        <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin: 0 0 16px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                            <i class="ti ti-info-circle" style="font-size: 16px; color: #666;"></i> Request Information
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px; background: #f8f9fa; padding: 12px 14px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                <i class="ti ti-shield-check" style="font-size: 18px; color: #666;"></i>
                                <div>
                                    <p style="margin: 0; font-size: 11px; color: #999; text-transform: uppercase; font-weight: 600;">Status</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #333; font-weight: 600;">500 Internal Error</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; background: #f8f9fa; padding: 12px 14px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                <i class="ti ti-server" style="font-size: 18px; color: #666;"></i>
                                <div>
                                    <p style="margin: 0; font-size: 11px; color: #999; text-transform: uppercase; font-weight: 600;">Server</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #333; font-weight: 600;">{{ request()->getHost() }}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; background: #f8f9fa; padding: 12px 14px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                <i class="ti ti-calendar" style="font-size: 18px; color: #666;"></i>
                                <div>
                                    <p style="margin: 0; font-size: 11px; color: #999; text-transform: uppercase; font-weight: 600;">Timestamp</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #333; font-weight: 600;">{{ now()->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; background: #f8f9fa; padding: 12px 14px; border-radius: 6px; border: 1px solid #e0e0e0;">
                                <i class="ti ti-clock" style="font-size: 18px; color: #666;"></i>
                                <div>
                                    <p style="margin: 0; font-size: 11px; color: #999; text-transform: uppercase; font-weight: 600;">Waktu</p>
                                    <p style="margin: 4px 0 0; font-size: 14px; color: #333; font-weight: 600;">{{ now()->format('H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Error Details (jika ada exception) --}}
                @if(isset($exception))
                <div class="card card-lg mb-5 text-start" style="border: 1px solid #dc3545; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);">
                    <div class="card-body" style="padding: 24px;">
                        {{-- Header --}}
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f0f0f0;">
                            <i class="ti ti-alert-circle" style="font-size: 24px; color: #dc3545;"></i>
                            <div>
                                <h5 style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">Exception Details</h5>
                                <p style="margin: 2px 0 0; font-size: 12px; color: #999;">Informasi lengkap tentang error yang terjadi</p>
                            </div>
                        </div>

                        {{-- Exception Type --}}
                        <div style="margin-bottom: 20px;">
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin: 0 0 8px; font-weight: 600;">Exception Type</p>
                            <div style="display: inline-block; background: linear-gradient(135deg, rgba(220,53,69,0.1), rgba(220,53,69,0.05)); border: 1px solid rgba(220,53,69,0.2); border-radius: 20px; padding: 8px 16px;">
                                <code style="color: #dc3545; font-size: 13px; font-weight: 600;">{{ class_basename($exception) }}</code>
                            </div>
                        </div>

                        {{-- Exception Message --}}
                        <div style="margin-bottom: 20px;">
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin: 0 0 8px; font-weight: 600;">Error Message</p>
                            <div style="background: linear-gradient(to right, rgba(220,53,69,0.05), rgba(220,53,69,0.02)); border-left: 4px solid #dc3545; border-radius: 4px; padding: 16px; word-break: break-word;">
                                <code style="color: #d32f2f; font-size: 14px; line-height: 1.6; font-family: 'Courier New', monospace; display: block; word-wrap: break-word;">{{ $exception->getMessage() ?: 'No message provided' }}</code>
                            </div>
                        </div>

                        {{-- File & Line --}}
                        <div style="margin-bottom: 20px;">
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin: 0 0 8px; font-weight: 600;">File Location</p>
                            <div style="display: flex; align-items: flex-start; gap: 12px; background: #f8f9fa; border-radius: 6px; padding: 12px 14px; border: 1px solid #e0e0e0;">
                                <i class="ti ti-file" style="font-size: 18px; color: #666; flex-shrink: 0; margin-top: 2px;"></i>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 13px; font-family: 'Courier New', monospace; color: #333; word-break: break-all; margin-bottom: 4px;">
                                        {{ $exception->getFile() }}
                                    </div>
                                    <div style="font-size: 13px; color: #dc3545; font-weight: 600;">
                                        <i class="ti ti-arrow-right" style="font-size: 12px;"></i> Line {{ $exception->getLine() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Stack Trace Preview --}}
                        @if(method_exists($exception, 'getTrace') && !empty($exception->getTrace()))
                        <div>
                            <p style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #999; margin: 0 0 8px; font-weight: 600;">Stack Trace (First 5 Frames)</p>
                            <div style="background: #1a1a1a; border-radius: 6px; overflow: hidden; border: 1px solid #333;">
                                <div style="max-height: 280px; overflow-y: auto;">
                                    @foreach($exception->getTrace() as $key => $trace)
                                        @if($key < 5)
                                        <div style="padding: 12px 14px; border-bottom: 1px solid #2a2a2a; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6;">
                                            <div style="color: #888; margin-bottom: 4px;">
                                                <span style="color: #666;">#{{ $key }}</span>
                                            </div>
                                            <div style="color: #50fa7b; margin-bottom: 2px;">
                                                @if(isset($trace['class']))
                                                    <span style="color: #bd93f9;">{{ $trace['class'] }}</span><span style="color: #f8f8f2;">{{ $trace['type'] ?? '' }}</span>
                                                @endif
                                                <span style="color: #8be9fd;">{{ $trace['function'] ?? 'unknown' }}</span><span style="color: #f8f8f2;">()</span>
                                            </div>
                                            @if(isset($trace['file']))
                                            <div style="color: #999; font-size: 11px;">
                                                📄 {{ basename($trace['file']) }}<span style="color: #666;">:</span><span style="color: #f92672;">{{ $trace['line'] ?? 'unknown' }}</span>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Buttons --}}
                <div class="d-flex gap-3 justify-content-center" style="flex-wrap: wrap;">
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg d-flex align-items-center gap-2" style="font-size: 15px; font-weight: 600; padding: 12px 28px; border: none;">
                        <i class="ti ti-home" style="font-size: 18px;"></i> Kembali ke Beranda
                    </a>
                    <a onclick="location.reload()" class="btn btn-white btn-lg d-flex align-items-center gap-2" style="cursor: pointer; font-size: 15px; font-weight: 600; padding: 12px 28px; border: 1px solid #ddd; background: #fff;">
                        <i class="ti ti-refresh" style="font-size: 18px;"></i> Coba Lagi
                    </a>
                </div>

            </div>
        </div>
    </section>
</main>
@endsection