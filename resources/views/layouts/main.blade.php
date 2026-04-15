<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Iseki Scan - Supply Part</title>
    <link rel="icon" type="image/x-icon" href="{{asset('img/logo-iseki.png')}}">

    <!-- Custom fonts for this template-->
    <link href="{{asset('vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
    {{--
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet"> --}}

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/sb-admin-2.min.css') }}?v=1" rel="stylesheet">

    @yield('style')

    <style>
        /* Jadikan submenu dua kolom */
        #collapseRequesting .collapse-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25rem 0.5rem;
        }

        #collapseRequesting .collapse-item {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            line-height: 1.2;
            margin: 0;
        }

        #collapseRequesting .collapse-header {
            grid-column: span 2;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        /* Fix Navbar Mobile agar tampil rapi dan sub-menu tidak terpotong */
        @media (max-width: 768px) {
            /* Sembunyikan sidebar secara default di mobile */
            #accordionSidebar {
                display: none;
                width: 6.5rem !important;
                z-index: 1060;
            }

            /* Munculkan sidebar saat burger button diklik (class sidebar-toggled dihapus) */
            body:not(.sidebar-toggled) #accordionSidebar {
                display: block !important;
                position: absolute !important; /* Gunakan absolute agar tidak memotong popup dan bisa discroll halaman */
                top: 0;
                left: 0;
                height: auto !important;
                min-height: 100vh;
                overflow: visible !important; /* Agar popup/fly-out tidak terpotong */
            }

            /* Sub-menu (collapse) jadi popup ke samping kanan */
            .sidebar .nav-item .collapse {
                position: absolute !important;
                left: 6.5rem !important;
                top: 0 !important;
                z-index: 1070 !important;
                width: max-content !important;
                display: none;
            }

            .sidebar .nav-item .collapse.show {
                display: block !important;
            }

            .sidebar .nav-item .collapse .collapse-inner {
                width: max-content !important;
                min-width: 14rem;
                box-shadow: 0 0.15rem 1.75rem 0 rgba(0, 0, 0, 0.15);
                background: white;
                border: 1px solid #e3e6f0;
                padding-bottom: 1rem !important;
            }

            /* Backdrop untuk menutup navbar saat klik di luar */
            #navbarBackdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1050;
            }

            body:not(.sidebar-toggled) #navbarBackdrop {
                display: block !important;
            }
        }
    </style>

    <!-- Dynamic Favicon -->
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("qr_code", "Part"); });</script>

    <!-- Dynamic Favicon Assets -->
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("qr_code", "Part"); });</script>
</head>

<body id="page-top" class="sidebar-toggled">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('/') }}">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Iseki Scan</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Action
            </div>

            <!-- Nav Item - Requesting (with area submenu) -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRequesting"
                    aria-expanded="true" aria-controls="collapseRequesting">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Requesting</span>
                </a>
                <div id="collapseRequesting" class="collapse" aria-labelledby="headingRequesting"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Request Menu:</h6>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}">Normal</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Main-1">Main-1</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=AGV">AGV</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Main-2">Main-2</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Main">Main</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Main-3">Main-3</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=LO">LO</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Main-4">Main-4</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-0">Sub-0</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Front-MK">Sub-Front-MK</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-1">Sub-1</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Front-HST">Sub-Front-HST</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-2">Sub-2</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Arm-MK">Sub-Arm-MK</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-3">Sub-3</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Arm-HST">Sub-Arm-HST</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-4">Sub-4</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Mid-HST">Sub-Mid-HST</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-5">Sub-5</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Gear-MK">Sub-Gear-MK</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-6">Sub-6</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Gear-HST">Sub-Gear-HST</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-7">Sub-7</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Cylinder-1">Sub-Cylinder-1</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-8">Sub-8</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Cylinder-2">Sub-Cylinder-2</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-9">Sub-9</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Cucian-Cylinder">Cucian-Cylinder</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-10">Sub-10</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Cucian-Houshing">Cucian-Houshing</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Sub-Houshing">Sub-Houshing</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=SXG-3">SXG-3</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Painting-A">Painting-A</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Painting-B">Painting-B</a>
                        <a class="collapse-item" href="{{ route('admin.requesting') }}?area=Palletina">Palletina</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Recording -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.recording') }}">
                    <i class="fas fa-fw fa-qrcode"></i>
                    <span>Recording</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Request
            </div>

            <!-- Nav Item - Report -->
            {{-- <li class="nav-item">
                <a class="nav-link" href="{{ route('admin_submission') }}">
            <i class="fas fa-fw fa-bullhorn"></i>
            <span>All</span></a>
            </li> --}}

            <!-- Nav Item - Report -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin_request') }}">
                    <i class="fas fa-fw fa-bullhorn"></i>
                    <span>Request</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Record
            </div>

            <!-- Nav Item - Monthly -->
            {{-- <li class="nav-item">
                <a class="nav-link" href="{{ route('monthly') }}">
            <i class="fas fa-fw fa-qrcode"></i>
            <span>All</span></a>
            </li> --}}

            <!-- Nav Item - Report -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('report') }}">
                    <i class="fas fa-fw fa-qrcode"></i>
                    <span>Record</span></a>
            </li>

            <!-- Divider -->
            {{--
            <hr class="sidebar-divider"> --}}

            <!-- Heading -->
            {{-- <div class="sidebar-heading">
                Validation
            </div> --}}

            <!-- Nav Item - Validation -->
            {{-- <li class="nav-item">
                <a class="nav-link" href="{{ route('validation') }}">
            <i class="fas fa-fw fa-file"></i>
            <span>Validation</span></a>
            </li> --}}

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Missing
            </div>

            <!-- Nav Item - Monthly -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('missing') }}">
                    <i class="fas fa-fw fa-ban"></i>
                    <span>Missing DST</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('missing.mc') }}">
                    <i class="fas fa-fw fa-ban"></i>
                    <span>Missing MC</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.missing.estimation') }}">
                    <i class="fas fa-fw fa-clock"></i>
                    <span>Missing Estimation</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.oke.estimation') }}">
                    <i class="fas fa-fw fa-check-circle"></i>
                    <span>Oke Estimation</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.urgents') }}">
                    <i class="fas fa-fw fa-exclamation-circle"></i>
                    <span>Urgent</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Evaluation
            </div>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('achievement') }}">
                    <i class="fas fa-fw fa-trophy"></i>
                    <span>Achievement</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('mistake') }}">
                    <i class="fas fa-fw fa-flag"></i>
                    <span>Mistake</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('forgot') }}">
                    <i class="fas fa-fw fa-question-circle"></i>
                    <span>Forgot</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Data
            </div>

            <!-- Nav Item - User -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user') }}">
                    <i class="fas fa-fw fa-user"></i>
                    <span>User</span></a>
            </li>

            <!-- Nav Item - Member -->
            <li class="nav-item">
                <a class="nav-link" href="{{route('member')}}">
                    <i class="fas fa-fw fa-user-circle"></i>
                    <span>Member</span></a>
            </li>

            <!-- Nav Item - Report -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('rack') }}">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Rack</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('wa.queue') }}">
                    <i class="fab fa-fw fa-whatsapp"></i>
                    <span>WA Queue</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-lg-inline text-gray-600 small">{{ session('Username_User') }}</span>
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                @yield('content')

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Iseki</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="{{ route('logout') }}">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>


    <!-- Core plugin JavaScript-->
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>

    @yield('script')

    <script>
        $(document).ready(function() {
            var table;

            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataTable')) {
                table = $('#dataTable').DataTable();
                table.page.len(100).draw(); // ✅ paksa default 100
            } else if ($.fn.DataTable) {
                table = $('#dataTable').DataTable({
                    pageLength: 100,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ]
                });
            }

            // Close sidebar when clicking backdrop on mobile
            $('#navbarBackdrop').on('click', function() {
                $('#sidebarToggleTop').click();
            });
        });
    </script>

    <div id="navbarBackdrop"></div>
</body>

</html>