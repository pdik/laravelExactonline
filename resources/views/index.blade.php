<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ExactOnline</title>

    {{-- Laravel Mix - CSS File --}}
    {{-- <link rel="stylesheet" href="{{ mix('css/exactonline.css') }}"> --}}

</head>
<body>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            @if($stats['UserName'] == '')
                <div class="col-lg-4 float-right">
                    <div class="exactOnline card card-margin">
                        <div class="exactOnline card-header no-border">
                            <h5 class="exactOnline card-title">{{ __('Exact online') }}</h5>
                        </div>
                        <div class="exactOnline card-body pt-0">
                            <div class="widget-49">
                                <div class="widget-49-title-wrapper">
                                    <div class="widget-49-date-primary">
                                        <span class="widget-49-date-day">E</span>
                                        <span class="widget-49-date-month"></span>
                                    </div>
                                    <div class="widget-49-meeting-info">
                                        <span class="widget-49-pro-title">{{__('Exact online')}}</span>
                                        <span class="widget-49-meeting-time"></span>
                                    </div>
                                </div>

                                <div class="widget-49-meeting-action">
                                    <a href="{{\Modules\ExactOnline\Entities\Exact::getLoginUrl()}}"
                                       type="submit"
                                       class="btn btn-success inline-flex items-center px-4 py-2 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-indigo focus:border-indigo-700 active:bg-indigo-700 transition duration-150 ease-in-out">
                                        Connect
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($stats['UserName'] != '')
                <div class="col-lg-4 float-right">
                    <div class="exactOnline card card-margin">
                        <div class="exactOnline card-header no-border">
                            <h5 class="exactOnline card-title">{{ __('Statastieken') }}</h5>
                        </div>
                        <div class="exactOnline card-body pt-0">
                            <div class="widget-49">
                                <div class="widget-49-title-wrapper">
                                    <div class="widget-49-date-primary">
                                        <span class="widget-49-date-day">{{ now()->day }}</span>
                                        <span class="widget-49-date-month">{{ now()->shortMonthName }}</span>
                                    </div>
                                    <div class="widget-49-meeting-info">
                                        <span class="widget-49-pro-title">{{ $stats['UserName'] }}</span>
                                        <span class="widget-49-meeting-time"></span>
                                    </div>
                                </div>
                                <div class="widget-49-meeting-points">
                                    <h4>Max Daily limit</h4>
                                    <div class="progress" style="height: 18px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{ $stats['dailyLimit'] / $stats['dailyLimitRemaining'] }}%;"
                                             aria-valuenow="{{ $stats['dailyLimitRemaining'] }}" aria-valuemin="0"
                                             aria-valuemax="{{ $stats['dailyLimit'] }}"></div>
                                    </div>
                                    <h4>Max limit per minuut</h4>
                                    <div class="progress" style="height: 18px;">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{$stats['minutelyLimit'] /  $stats['minutelyLimitRemaining']  }}%;"
                                             aria-valuenow="{{ $stats['minutelyLimitRemaining'] }}" aria-valuemin="0"
                                             aria-valuemax="{{ $stats['minutelyLimit'] }}"></div>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            @endif
            <div class="col-lg-4 float-right">
                <div class="exactOnline card card-margin">
                    <div class="exactOnline card-header no-border">
                        <h5 class="exactOnline card-title">{{ __('Exact online') }}</h5>
                    </div>
                    <div class="exactOnline card-body pt-0">
                        <div class="widget-49">
                            <div class="widget-49-title-wrapper">
                                <div class="widget-49-date-primary">
                                    <span class="widget-49-date-day">E</span>
                                    <span class="widget-49-date-month"></span>
                                </div>
                                <div class="widget-49-meeting-info">
                                    <span class="widget-49-pro-title">{{__('Exact online webhook')}}</span>
                                    <span class="widget-49-meeting-time"></span>
                                </div>
                            </div>

                            <div class="widget-49-meeting-action">
                                <form action="{{ route('exact-online.set-webhook') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button
                                            type="submit"
                                            class="btn btn-success inline-flex items-center px-4 py-2 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:shadow-outline-indigo focus:border-indigo-700 active:bg-indigo-700 transition duration-150 ease-in-out">
                                        Register webhook
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Laravel Mix - JS File --}}
{{-- <script src="{{ mix('js/exactonline.js') }}"></script> --}}
</body>
</html>



