<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title> {{ config('app.name') }} | ADMIN Dashboard </title>

    <style>
        body {
            font-size: 14px !important;
            font-family: 'Inter', 'Roboto', 'Segoe UI', 'Helvetica Neue', sans-serif !important;
        }
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #237a3d 0%, #047c37 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #ad4059 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem 2rem;
            border: none;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-badge {
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
        }

        /* Search and Filter Bar */
        .notification-controls {
            padding: 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .filter-pill {
            padding: 0.375rem 1rem;
            border-radius: 20px;
            border: 2px solid #e9ecef;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .filter-pill:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-pill.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        /* Notification List */
        .notification-list {
            max-height: 55vh;
            overflow-y: auto;
            padding: 1rem;
        }

        .notification-list::-webkit-scrollbar {
            width: 8px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .notification-item {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #667eea;
        }

        .notification-item.active {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-color: #667eea;
        }

        .notification-item.unread {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .notification-item.unread::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #667eea;
            border-radius: 50%;
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
        }

        .notification-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .notification-icon.success {
            background: var(--success-gradient);
        }

        .notification-icon.warning {
            background: var(--warning-gradient);
        }

        .notification-icon.info {
            background: var(--info-gradient);
        }

        .notification-icon.default {
            background: var(--primary-gradient);
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: #212529;
            margin: 0;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-left: auto;
        }

        .notification-preview-text {
            font-size: 0.875rem;
            color: #6c757d;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Preview Panel */
        .notification-preview {
            padding: 2rem;
            max-height: 55vh;
            overflow-y: auto;
        }

        .preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #adb5bd;
        }

        .preview-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .preview-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .preview-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.75rem;
        }

        .preview-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .preview-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-body {
            font-size: 1rem;
            line-height: 1.7;
            color: #495057;
        }

        .preview-actions {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e9ecef;
            display: flex;
            gap: 0.75rem;
        }

        .btn-action {
            padding: 0.625rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary-gradient {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-custom {
            background: white;
            border: 2px solid #e9ecef;
            color: #495057;
        }

        .btn-outline-custom:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #adb5bd;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* ============================================
           MAKE ALL MODAL CLOSE BUTTONS RED & VISIBLE
           ============================================ */

        /* Target all Bootstrap close buttons in modals */
        .modal .btn-close {
            opacity: 1 !important;
            background-size: 20px;
            width: 32px;
            height: 32px;
            filter: invert(15%) sepia(93%) saturate(5841%) hue-rotate(356deg) brightness(91%) contrast(119%);
        }

        /* Hover effect - darker red */
        .modal .btn-close:hover {
            filter: invert(10%) sepia(100%) saturate(7000%) hue-rotate(356deg) brightness(80%) contrast(119%);
            transform: scale(1.1);
            transition: all 0.2s ease;
        }

        /* Focus state */
        .modal .btn-close:focus {
            box-shadow: 0 0 0 0.25rem rgb(241, 26, 12);
            outline: none;
        }

        /* Active/pressed state */
        .modal .btn-close:active {
            transform: scale(0.95);
        }
    </style>
    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/assets/img/favicons/icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/assets/img/favicons/logo-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('/assets/img/favicons/logo-16x16.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ url('/assets/img/favicons/logo.png') }}">
    <link rel="manifest" href="{{ url('/assets/img/favicons/manifest.json') }}">
    <meta name="msapplication-TileImage" content="{{ url('/assets/img/favicons/150x150.png') }}">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link href="{{ url('/vendors/datatables.net-bs5/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"/>
    <link href="{{ url('vendors/choices/choices.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('/assets/js/config.js') }}"></script>
    <script src="{{ url('/vendors/overlayscrollbars/OverlayScrollbars.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
{{--    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">--}}
    <link href="{{ url('/vendors/overlayscrollbars/OverlayScrollbars.min.css') }}" rel="stylesheet">
    <link href="{{ url('/assets/css/theme-rtl.css') }}" rel="stylesheet" id="style-rtl">
    <link href="{{ url('/assets/css/theme.css') }}" rel="stylesheet" id="style-default">
    <link href="{{ url('/assets/css/user-rtl.css') }}" rel="stylesheet" id="user-style-rtl">
    <link href="{{ url('/assets/css/user.css') }}" rel="stylesheet" id="user-style-default">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /*.card {
            min-height: 80vh !important;
        }*/
        .tab-pane {
            /*min-height: 72vh !important;*/
        }
    </style>
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
      document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll('.col-6.col-sm-auto.d-flex.align-items-center.pe-0').forEach(element => {
              let button = document.createElement("a");
              button.innerText = "Go Back";
              button.classList.add("link", "link-info", "mx-2");
              button.onclick = function() {
                  window.history.back();
              };
              element.appendChild(button); // Append the button inside each matching element
          });
      });
    </script>
  </head>

  <body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container-fluid">
        <script>
          var isFluid = JSON.parse(localStorage.getItem('isFluid'));
          if (isFluid) {
            var container = document.querySelector('[data-layout]');
            container.classList.remove('container');
            container.classList.add('container-fluid');
          }
        </script>
        @yield('main-content')
          @include('partials.messages')

      </div>

        <div class="modal fade" id="notificationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            <span>🔔</span>
                            Notifications
                            <span class="notification-badge" id="notificationCount">0</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Search and Filter Controls -->
                    <div class="notification-controls">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="search-box">
                                    <span class="search-icon">🔍</span>
                                    <input type="text" class="form-control" id="searchNotifications" placeholder="Search notifications...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="filter-pills">
                                    <button class="filter-pill active" data-filter="all">All</button>
                                    <button class="filter-pill" data-filter="unread">Unread</button>
                                    <button class="filter-pill" data-filter="important">Important</button>
                                    <button class="filter-pill" data-filter="read">Read</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row g-0">

                            <!-- LEFT COLUMN: Notification List -->
                            <div class="col-md-4 border-end">
                                <div class="notification-list" id="notificationList">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">⏳</div>
                                        <p>Loading notifications...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN: Notification Preview -->
                            <div class="col-md-8">
                                <div class="notification-preview" id="notificationPreview">
                                    <div class="preview-empty">
                                        <div class="preview-empty-icon">📭</div>
                                        <h4 class="fw-bold">Select a notification</h4>
                                        <p>Choose a notification from the list to view details</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
            // Helper function to format names (capitalize first letter of each word)
            function formatName(firstName, surname = '') {
                const fullName = `${firstName} ${surname}`.trim();
                return fullName.split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
            }

            // Helper function to get relative time
            function getRelativeTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffMs = now - date;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);

                if (diffMins < 1) return 'Just now';
                if (diffMins < 60) return `${diffMins}m ago`;
                if (diffHours < 24) return `${diffHours}h ago`;
                if (diffDays < 7) return `${diffDays}d ago`;
                return date.toLocaleDateString();
            }

            // Update notification count
            function updateNotificationCount() {
                const unreadCount = document.querySelectorAll('.notification-item.unread').length;
                document.getElementById('notificationCount').textContent = unreadCount;
            }

            // Function to load notifications from server
            function loadNotifications() {
                fetch(`{{ route('admin.notifications') }}`)
                    .then(res => res.json())
                    .then(data => {
                        let list = document.getElementById('notificationList');
                        list.innerHTML = '';

                        if (data.length === 0) {
                            list.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📭</div><p>No notifications yet</p></div>';
                            return;
                        }

                        data.forEach(item => {
                            const creatorName = item.notification.creator
                                ? formatName(item.notification.creator.first_name, item.notification.creator.surname || '')
                                : 'System';

                            const relativeTime = getRelativeTime(item.created_at);
                            const isUnread = !item.is_read;
                            const notificationType = item.notification.type || 'default';

                            const iconMap = {
                                success: '✓',
                                warning: '⚠',
                                info: 'ℹ',
                                default: '📧'
                            };

                            list.innerHTML += `
                        <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${item.id}" data-type="${notificationType}">
                            <div class="notification-header">
                                <div class="notification-icon ${notificationType}">${iconMap[notificationType] || '📧'}</div>
                                <div>
                                    <p class="notification-title">${item.notification.title}</p>
                                </div>
                                <span class="notification-time">${relativeTime}</span>
                            </div>
                            <p class="notification-preview-text">By: ${creatorName} • ${new Date(item.created_at).toLocaleString()}</p>
                        </div>
                    `;
                        });

                        updateNotificationCount();
                    })
                    .catch(err => {
                        console.error('Error loading notifications:', err);
                        document.getElementById('notificationList').innerHTML =
                            '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Failed to load notifications</p></div>';
                    });
            }

            // Add event listener to the bell icon - use event delegation on document
            document.addEventListener('click', function(e) {
                const loadBtn = e.target.closest('#loadNotifications');
                if (loadBtn) {
                    e.preventDefault(); // Prevent the # link from navigating
                    // Modal will open automatically via data-bs-toggle
                }
            });

            // Load details when clicking a notification
            document.addEventListener('click', function(e) {
                const item = e.target.closest('.notification-item');
                if (!item) return;

                let id = item.dataset.id;

                // Remove active class from all items
                document.querySelectorAll('.notification-item').forEach(i => i.classList.remove('active'));

                // Add active class to clicked item
                item.classList.add('active');
                item.classList.remove('unread');

                // Update count
                updateNotificationCount();

                // Fetch notification details
                fetch('admin/notifications/' + id)
                    .then(res => res.json())
                    .then(data => {
                        let info = data.details.notification;
                        const creatorName = info.creator
                            ? formatName(info.creator.first_name, info.creator.surname || '')
                            : 'System';

                        const iconMap = {
                            success: '✓',
                            warning: '⚠',
                            info: 'ℹ',
                            default: '📧'
                        };

                        const notificationType = info.type || 'default';

                        let html = `
                    <div class="preview-header">
                        <div class="notification-icon ${notificationType} mb-3" style="width: 60px; height: 60px; font-size: 2rem;">
                            ${iconMap[notificationType] || '📧'}
                        </div>
                        <h3 class="preview-title">${info.title}</h3>
                        <div class="preview-meta">
                            <div class="preview-meta-item">
                                <span>👤</span>
                                <span>${creatorName}</span>
                            </div>
                            <div class="preview-meta-item">
                                <span>🕐</span>
                                <span>${new Date(info.created_at).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    <div class="preview-body">
                        ${info.message}
                        ${info.data ? `
                            <ul class="mt-3">
                                ${info.data.task_id ? `<li><strong>Task Number:</strong> ${info.data.task_id}</li>` : ''}
                                ${info.data.deadline ? `<li><strong>Task Deadline:</strong> ${formatDeadline(info.data.deadline)}</li>` : ''}
                                ${info.data.description ? `<li><strong>Description:</strong> ${info.data.description}</li>` : ''}
                            </ul>
                        ` : ''}
                    </div>
                `;

                        // If admin → show reading statuses
                        if (data.readers) {
                            html += `
                        <div class="preview-actions" style="flex-direction: column; align-items: stretch;">
                            <hr style="margin: 1.5rem 0;">
                            <h5 class="mb-3"><span style="color: #11998e;">✓</span> Read By (${data.readers.read_by.length})</h5>
                            <ul class="list-unstyled mb-4" style="max-height: 150px; overflow-y: auto;">
                                ${data.readers.read_by.map(u => `
                                    <li class="mb-2" style="padding: 0.5rem; background: #f8f9fa; border-radius: 8px;">
                                        ${formatName(u.first_name, u.surname || '')}
                                    </li>
                                `).join('')}
                            </ul>
                            <h5 class="mb-3 mt-3"><span style="color: #f5576c;">⏱</span> Pending (${data.readers.pending.length})</h5>
                            <ul class="list-unstyled" style="max-height: 150px; overflow-y: auto;">
                                ${data.readers.pending.map(u => `
                                    <li class="mb-2" style="padding: 0.5rem; background: #f8f9fa; border-radius: 8px;">
                                        ${formatName(u.first_name, u.surname || '')}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;

                        }

                        document.getElementById('notificationPreview').innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error loading notification details:', err);
                        document.getElementById('notificationPreview').innerHTML = `
                    <div class="preview-empty">
                        <div class="preview-empty-icon">⚠️</div>
                        <h4 class="fw-bold">Error Loading Notification</h4>
                        <p>Unable to load notification details. Please try again.</p>
                    </div>
                `;
                    });
            });

            // Helper function to format deadline
            function formatDeadline(dateString) {
                const date = new Date(dateString);

                // Day names
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const dayName = days[date.getDay()];

                // Get date components
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');

                return `${dayName}, ${day}-${month}-${year} ${hours}:${minutes}`;
            }

            // Search functionality
            document.getElementById('searchNotifications').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const items = document.querySelectorAll('.notification-item');

                items.forEach(item => {
                    const title = item.querySelector('.notification-title')?.textContent.toLowerCase() || '';
                    const preview = item.querySelector('.notification-preview-text')?.textContent.toLowerCase() || '';

                    if (title.includes(searchTerm) || preview.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });

            // Auto-load notifications when modal opens
            const notificationModal = document.getElementById('notificationModal');
            if (notificationModal) {
                notificationModal.addEventListener('shown.bs.modal', function () {
                    loadNotifications(); // Call the function directly instead of clicking a button
                });
            }
        </script>
    </main>
    <!-- ===============================================-->
    <!--    End of Main Content-->
    <!-- ===============================================-->

{{--    @include('admin::partials.setting-panel')--}}

    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
    <script src="{{ url('/vendors/popper/popper.min.js') }}"></script>
    <script src="{{ url('/vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ url('/vendors/anchorjs/anchor.min.js') }}"></script>
    <script src="{{ url('/vendors/is/is.min.js') }}"></script>
    <script src="{{ url('/vendors/echarts/echarts.min.js') }}"></script>
    <script src="{{ url('/vendors/fontawesome/all.min.js') }}"></script>
    <script src="{{ url('/vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ url('/vendors/countup/countUp.umd.js') }}"></script>
{{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-polyfills/0.1.43/polyfill.min.js"></script>--}}
{{--    <script src="https://polyfill.io/v3/polyfill.min.js?features=window.scroll"></script>--}}
    <script src="{{ url('/vendors/list.js/list.min.js') }}"></script>
    <script src="{{ url('/assets/js/theme.js') }}"></script>
    <script src="{{ url('vendors/choices/choices.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs5/dataTables.bootstrap5.min.js') }}"> </script>
    <script src="{{ asset('vendors/datatables.net-fixedcolumns/dataTables.fixedColumns.min.js') }}"> </script>

  </body>

</html>
