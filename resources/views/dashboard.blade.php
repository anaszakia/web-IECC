@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- row 1: Welcome + Ideas --}}
    <div class="row mb-6 g-6">
        <div class="col-xl-8 col-lg-6">
            <div class="bg-gradient-mixed p-8 py-10 rounded-3 p-lg-7">
                <h1 class="fs-3">Selamat Datang, {{ session('user_name') }}!</h1>
                <p class="mb-0">Selamat datang di Dashboard Anda</p>
                <p>Anda dapat mengakses berbagai fitur dan menu di sini.</p>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="card card-lg">
                <div class="card-body">
                    <div class="mb-4 position-relative py-2">
                        <h5 class="mb-1">Ideas for You</h5>
                        <div class="swiper-navigation position-absolute top-50 end-10 me-4 me-lg-8 me-xl-4">
                            <div class="swiper-button-prev ms-n3"></div>
                            <div class="swiper-button-next ms-6"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="swiper-container swiper" id="swiper-1" data-speed="900"
                                data-space-between="100" data-pagination="false" data-navigation="true"
                                data-autoplay="false" data-autoplay-delay="2000"
                                data-breakpoints='{"480":{"slidesPerView":1},"768":{"slidesPerView":1},"1024":{"slidesPerView":1},"1200":{"slidesPerView":1}}'>
                                <div class="swiper-wrapper">
                                    <div class="swiper-slide">
                                        <h4>Create a Blog Post for your product</h4>
                                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                        <a href="#!" class="btn btn-white btn-sm">Read Now</a>
                                    </div>
                                </div>
                                <div class="swiper-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- row 2: Stats cards --}}
    <div class="row row-cols-1 row-cols-xl-3 row-cols-md-3 mb-6 g-6">
        <div class="col">
            <div class="card card-lg">
                <div class="card-body d-flex flex-column gap-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-shape icon-lg rounded-circle bg-warning-darker text-warning-lighter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-shopping-cart">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/>
                            </svg>
                        </div>
                        <div>Orders</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center lh-1">
                        <div class="fs-3 fw-bold">5,312</div>
                        <div class="text-success small">2.29% ↑</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-lg">
                <div class="card-body d-flex flex-column gap-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-shape icon-lg rounded-circle bg-success-darker text-success-lighter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-coin">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M14.8 9a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"/><path d="M12 7v10"/>
                            </svg>
                        </div>
                        <div>Revenue</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center lh-1">
                        <div class="fs-3 fw-bold">$120,000</div>
                        <div class="text-warning small">2.19% ↑</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-lg">
                <div class="card-body d-flex flex-column gap-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-shape icon-lg rounded-circle bg-info-darker text-info-lighter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icon-tabler-user-circle">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 10m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M6.168 18.849a4 4 0 0 1 3.832 -2.849h4a4 4 0 0 1 3.834 2.855"/>
                            </svg>
                        </div>
                        <div>Conversion Rate</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center lh-1">
                        <div class="fs-3 fw-bold">3.5%</div>
                        <div class="text-danger small">3.19% ↓</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- row 3: Revenue chart + Product Sales --}}
    <div class="row g-6 mb-6">
        <div class="col-xl-8 col-12">
            <div class="card card-lg">
                <div class="card-body d-flex flex-column gap-5">
                    <h5 class="mb-0">Revenue</h5>
                    <div class="bg-gray-100 p-3 rounded-3">
                        <ul class="nav nav-pills-white nav-fill" id="chartTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#current-week" type="button">
                                    <span class="d-flex flex-column">
                                        <span>Total Income</span>
                                        <span class="text-start fs-3 fw-semibold mt-2">$120,000</span>
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#past-week" type="button">
                                    <span class="d-flex flex-column">
                                        <span>Total Expenses</span>
                                        <span class="text-start fs-3 fw-semibold mt-2">$198,214</span>
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="current-week"><div id="totalIncomeChart"></div></div>
                        <div class="tab-pane fade" id="past-week"><div id="totalExpensesChart"></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-12">
            <div class="card card-lg">
                <div class="card-body">
                    <h5 class="mb-6">Product Sales</h5>
                    <div id="totalSale" class="d-flex justify-content-center"></div>
                    <table class="table table-sm table-borderless mb-0 mt-5">
                        <tbody>
                            <tr>
                                <td>Smartphones</td>
                                <td class="text-end">$22,120 <span class="text-secondary">38.1%</span></td>
                            </tr>
                            <tr>
                                <td>Laptops</td>
                                <td class="text-end">$4,510 <span class="text-secondary">28.6%</span></td>
                            </tr>
                            <tr>
                                <td>Headphones</td>
                                <td class="text-end">$800 <span class="text-secondary">23.8%</span></td>
                            </tr>
                            <tr>
                                <td>Cameras</td>
                                <td class="text-end">$420 <span class="text-secondary">9.5%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- row 4: Orders table --}}
    <div class="row g-6 mb-6">
        <div class="col-xl-8">
            <div class="card card-lg">
                <div class="card-header border-bottom-0"><h5 class="mb-0">Orders</h5></div>
                <div class="table-responsive">
                    <table class="table text-nowrap mb-0 table-centered table-hover">
                        <thead>
                            <tr><th>Order ID</th><th>Amount</th><th>Shipping Method</th><th>Delivery Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>#DU005</td><td>$150</td><td>Standard</td><td>Jan 20, 2025</td><td><span class="badge text-info-emphasis bg-info-subtle">Shipped</span></td><td><a href="#!" class="btn btn-white btn-sm">View</a></td></tr>
                            <tr><td>#DU004</td><td>$200</td><td>Express</td><td>Jan 22, 2025</td><td><span class="badge text-warning-emphasis bg-warning-subtle">Pending</span></td><td><a href="#!" class="btn btn-white btn-sm">View</a></td></tr>
                            <tr><td>#DU003</td><td>$300</td><td>Overnight</td><td>Jan 18, 2025</td><td><span class="badge text-danger-emphasis bg-danger-subtle">Cancel</span></td><td><a href="#!" class="btn btn-white btn-sm">View</a></td></tr>
                            <tr><td>#DU002</td><td>$560</td><td>Overnight</td><td>Jan 13, 2025</td><td><span class="badge text-success-emphasis bg-success-subtle">Completed</span></td><td><a href="#!" class="btn btn-white btn-sm">View</a></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card card-lg">
                <div class="card-body">
                    <h5 class="mb-6">Revenue by Location</h5>
                    <div id="map-world" style="width: 100%; height: 250px"></div>
                    <div class="d-flex flex-column gap-2 mt-3">
                        <div>
                            <div class="d-flex justify-content-between"><span>United States</span><span>$22,120</span></div>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar" style="width:45%"></div></div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><span>India</span><span>$12,756</span></div>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-success" style="width:25%"></div></div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><span>United Kingdom</span><span>$8,864</span></div>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-info" style="width:38%"></div></div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between"><span>Sweden</span><span>$6,124</span></div>
                            <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-warning" style="width:18%"></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
