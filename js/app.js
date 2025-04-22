/**
 * app.js - JavaScript chính cho ứng dụng quản lý đỗ xe thông minh
 */

// Sử dụng strict mode để tránh lỗi phổ biến
"use strict";

// Hàm tiện ích để gọi API (sử dụng Fetch API)
// Trả về Promise chứa dữ liệu JSON hoặc ném lỗi
async function fetchApi(endpoint, options = {}) {
  const defaultHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  // Mặc định dùng GET nếu không có method được chỉ định
  const config = {
    method: options.method || "GET",
    headers: { ...defaultHeaders, ...options.headers }, // Gộp header mặc định và header tùy chỉnh
    ...options, // Gộp các options khác như body
  };

  // Nếu là POST hoặc PUT và có body, tự động JSON.stringify
  if (
    (config.method === "POST" || config.method === "PUT") &&
    config.body &&
    typeof config.body !== "string"
  ) {
    // Nếu body là FormData, không set Content-Type JSON và không stringify
    if (!(config.body instanceof FormData)) {
      config.body = JSON.stringify(config.body);
    } else {
      // Với FormData, trình duyệt tự set Content-Type là multipart/form-data
      delete config.headers["Content-Type"];
    }
  }
  // Nếu method là GET, không cần gửi body
  if (config.method === "GET" && config.body) {
    delete config.body;
  }

  // Thay thế '/smart_parking/' bằng đường dẫn gốc thực tế của bạn nếu cần
  const baseUrl = "/parking-manager/"; // Hoặc lấy từ biến môi trường / config
  const url = `${baseUrl}api/${endpoint}`;

  try {
    const response = await fetch(url, config);

    if (!response.ok) {
        // Đọc body lỗi MỘT LẦN DUY NHẤT dưới dạng text
        const errorText = await response.text();
        // Mặc định coi lỗi là text thô
        let errorData = { message: errorText || `Lỗi HTTP: ${response.status}` };
    
        // Cố gắng phân tích text đó thành JSON
        try {
            const jsonData = JSON.parse(errorText);
            // Nếu parse thành công và có thuộc tính message, thì sử dụng nó
            if (jsonData && jsonData.message) {
                 errorData = jsonData; // Ghi đè với đối tượng JSON nếu hợp lệ
            } else if (typeof jsonData === 'object' && jsonData !== null) {
                 // Nếu là JSON nhưng không có message, giữ lại dạng JSON
                 errorData = jsonData;
            }
        } catch (parseError) {
             // Nếu không parse được JSON, giữ nguyên lỗi dạng text đã đọc
             console.warn("Phản hồi lỗi từ API không phải là JSON hợp lệ:", errorText);
        }
    
        console.error('API Error Response:', errorData);
        // Ném lỗi với message đã được xử lý
        // Cần kiểm tra xem errorData có phải object và có message không
        let errorMessage = `Lỗi không xác định từ API (Status: ${response.status})`;
        if (typeof errorData === 'object' && errorData !== null && errorData.message) {
            errorMessage = errorData.message;
        } else if (typeof errorData === 'string') { // Nếu errorData cuối cùng là string
            errorMessage = errorData;
        }
        throw new Error(errorMessage);
    }

    // Xử lý trường hợp API trả về No Content (204)
    if (response.status === 204) {
      return null; // Hoặc trả về một giá trị chỉ định không có nội dung
    }

    // Parse JSON nếu có nội dung
    return await response.json();
  } catch (error) {
    console.error("Fetch API Error:", error);
    // Ném lại lỗi để nơi gọi có thể xử lý
    throw error;
  }
}

// Hàm hiển thị thông báo (sử dụng Bootstrap Alerts nếu có)
function showAlert(message, type = "info", containerId = "alert-container") {
  const alertContainer = document.getElementById(containerId);
  if (!alertContainer) {
    console.warn(`Alert container with id '${containerId}' not found.`);
    alert(`${type.toUpperCase()}: ${message}`); // Fallback to browser alert
    return;
  }

  const wrapper = document.createElement("div");
  wrapper.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
  alertContainer.innerHTML = ""; // Xóa thông báo cũ (tùy chọn)
  alertContainer.append(wrapper);

  // Tự động ẩn sau 5 giây (tùy chọn)
  // setTimeout(() => {
  //     const alertElement = wrapper.querySelector('.alert');
  //     if (alertElement) {
  //         bootstrap.Alert.getOrCreateInstance(alertElement).close();
  //     }
  // }, 5000);
}

// --- Logic cho trang Dashboard ---
function initDashboard() {
  const dashboardContainer = document.getElementById("dashboard-container");
  if (!dashboardContainer) return; // Chỉ chạy nếu đang ở trang dashboard

  console.log("Initializing Dashboard...");

  const overviewElements = {
    total: document.getElementById("total-slots"),
    occupied: document.getElementById("occupied-slots"),
    available: document.getElementById("available-slots"),
    // reserved: document.getElementById('reserved-slots'), // Nếu có
    espStatus: document.getElementById("esp-connection-status"),
  };
  const slotsContainer = document.getElementById("slots-container"); // Nơi chứa các div slot
  const openBarrierBtn = document.getElementById("openBarrierBtn");
  const parkingChartCanvas = document.getElementById("parkingChart");
  let parkingChart = null; // Biến để lưu trữ đối tượng Chart

  // Hàm cập nhật dữ liệu Dashboard từ API
  async function updateDashboardData() {
    console.log("Fetching dashboard data...");
    try {
      const data = await fetchApi("get_status.php");

      if (data && data.status === "success") {
        // 1. Cập nhật thông tin tổng quan
        if (overviewElements.total)
          overviewElements.total.textContent = data.overview.total || 0;
        if (overviewElements.occupied)
          overviewElements.occupied.textContent = data.overview.occupied || 0;
        if (overviewElements.available)
          overviewElements.available.textContent = data.overview.available || 0;
        // if (overviewElements.reserved) overviewElements.reserved.textContent = data.overview.reserved || 0;

        if (overviewElements.espStatus) {
          let statusText = "Không rõ";
          let statusClass = "text-muted";
          switch (data.overview.esp_connection) {
            case "online":
              statusText = "Đang kết nối";
              statusClass = "text-success";
              break;
            case "offline":
              statusText = "Mất kết nối";
              statusClass = "text-danger";
              break;
            default:
              statusText = "Không rõ";
              statusClass = "text-warning";
          }
          overviewElements.espStatus.textContent = `Trạng thái ESP: ${statusText}`;
          overviewElements.espStatus.className = `fw-bold ${statusClass}`; // Reset class và set class mới
        }

        // 2. Cập nhật trạng thái từng Slot (Giả sử có các div slot trong slotsContainer)
        if (slotsContainer && data.slots) {
          slotsContainer.innerHTML = ""; // Xóa các slot cũ trước khi vẽ lại
          data.slots.forEach((slot) => {
            const slotDiv = document.createElement("div");
            slotDiv.id = `slot-${slot.slot_id}`;
            slotDiv.classList.add("col-md-3", "mb-3"); // Ví dụ layout Bootstrap

            let cardClass = "bg-light";
            let statusIcon = "fa-question-circle"; // Icon mặc định
            let statusText = "Không xác định";
            let userInfo = "-";

            switch (slot.status) {
              case "available":
                cardClass = "bg-success-subtle"; // Dùng màu nhẹ của Bootstrap 5.3+
                statusIcon = "fa-check-circle";
                statusText = "Trống";
                break;
              case "occupied":
                cardClass = "bg-danger-subtle";
                statusIcon = "fa-times-circle";
                statusText = `Đã đỗ ${
                  slot.user_name ? `(${slot.user_name})` : "(Không rõ)"
                }`;
                userInfo = slot.user_name || "Không rõ";
                if (slot.occupied_since) {
                  // Định dạng thời gian nếu cần
                  const occupiedTime = new Date(
                    slot.occupied_since
                  ).toLocaleString("vi-VN");
                  statusText += `<br><small>Từ: ${occupiedTime}</small>`;
                }
                break;
              case "reserved": // Nếu có
                cardClass = "bg-warning-subtle";
                statusIcon = "fa-pause-circle";
                statusText = "Đã đặt trước";
                break;
              case "maintenance": // Nếu có
                cardClass = "bg-secondary-subtle";
                statusIcon = "fa-tools";
                statusText = "Đang bảo trì";
                break;
            }

            // Thêm class đặc biệt nếu slot là đặc biệt
            if (slot.is_special) {
              cardClass += " border border-primary border-2"; // Đánh dấu slot đặc biệt
            }

            slotDiv.innerHTML = `
                             <div class="card h-100 ${cardClass}">
                                 <div class="card-body text-center">
                                     <h5 class="card-title">${slot.slot_name} ${
              slot.is_special ? '<i class="fas fa-star text-warning"></i>' : ""
            }</h5>
                                     <p class="card-text">
                                         <i class="fas ${statusIcon} me-2"></i> ${statusText}
                                     </p>
                                     <!-- <p class="card-text small">Người dùng: ${userInfo}</p> -->
                                 </div>
                             </div>
                         `;
            slotsContainer.appendChild(slotDiv);
          });
        }

        // 3. Cập nhật biểu đồ Chart.js
        if (parkingChart) {
          const { available, occupied, reserved } = data.overview;
          parkingChart.data.datasets[0].data = [
            available || 0,
            occupied || 0,
            reserved || 0,
          ]; // Cập nhật dữ liệu
          parkingChart.update(); // Vẽ lại biểu đồ
        }
      } else {
        showAlert(
          data.message || "Không thể lấy dữ liệu trạng thái.",
          "danger"
        );
      }
    } catch (error) {
      console.error("Error updating dashboard:", error);
      showAlert(`Lỗi khi cập nhật dashboard: ${error.message}`, "danger");
      // Có thể dừng interval nếu lỗi liên tục
      // clearInterval(dashboardUpdateInterval);
    }
  }

  // Khởi tạo biểu đồ nếu có canvas
  if (parkingChartCanvas) {
    const ctx = parkingChartCanvas.getContext("2d");
    parkingChart = new Chart(ctx, {
      type: "doughnut", // Hoặc 'pie', 'bar'
      data: {
        labels: ["Còn trống", "Đã đỗ", "Đặt trước"], // Thêm/bớt label tương ứng
        datasets: [
          {
            label: "Trạng thái bãi đỗ",
            // Dữ liệu ban đầu hoặc lấy từ lần fetch đầu tiên
            data: [
              overviewElements.available?.textContent || 0,
              overviewElements.occupied?.textContent || 0,
              0,
            ], // Dữ liệu ban đầu
            backgroundColor: [
              "rgba(40, 167, 69, 0.7)", // Xanh lá (Trống)
              "rgba(220, 53, 69, 0.7)", // Đỏ (Đã đỗ)
              "rgba(255, 193, 7, 0.7)", // Vàng (Đặt trước)
            ],
            borderColor: [
              "rgba(40, 167, 69, 1)",
              "rgba(220, 53, 69, 1)",
              "rgba(255, 193, 7, 1)",
            ],
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false, // Cho phép tùy chỉnh kích thước tốt hơn
        plugins: {
          legend: {
            position: "top",
          },
          title: {
            display: true,
            text: "Tỉ lệ chỗ đỗ xe",
          },
        },
      },
    });
  }

  // Xử lý nút mở Barrier
  if (openBarrierBtn) {
    openBarrierBtn.addEventListener("click", async () => {
      openBarrierBtn.disabled = true; // Vô hiệu hóa nút trong khi xử lý
      openBarrierBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang mở...';

      try {
        // Quan trọng: Đảm bảo API open_barrier.php dùng POST hoặc cách khác phù hợp
        // Fetch API mặc định là GET, cần chỉ định method POST
        const result = await fetchApi("open_barrier.php", { method: "POST" });
        if (result && result.status === "success") {
          showAlert("Đã gửi lệnh mở Barrier thành công!", "success");
        } else {
          // Lỗi đã được xử lý trong fetchApi và ném ra
          showAlert(result.message || "Có lỗi xảy ra khi gửi lệnh.", "danger");
        }
      } catch (error) {
        showAlert(`Lỗi khi mở barrier: ${error.message}`, "danger");
      } finally {
        openBarrierBtn.disabled = false; // Kích hoạt lại nút
        openBarrierBtn.innerHTML =
          '<i class="fas fa-door-open me-2"></i> Mở Barrier Thủ Công';
      }
    });
  }

  // Gọi hàm cập nhật lần đầu khi trang tải xong
  updateDashboardData();

  // Tự động cập nhật sau mỗi 5 giây (5000ms)
  const dashboardUpdateInterval = setInterval(updateDashboardData, 5000);

  // Dọn dẹp interval khi rời trang (nếu là SPA, còn không thì không cần)
  // window.addEventListener('beforeunload', () => {
  //    clearInterval(dashboardUpdateInterval);
  // });
}

// --- Logic cho trang Lịch sử ---
function initHistoryPage() {
  const historyContainer = document.getElementById("history-container");
  if (!historyContainer) return;

  console.log("Initializing History Page...");

  const filterForm = document.getElementById("history-filters");
  const searchInput = document.getElementById("history-search");
  const slotFilter = document.getElementById("history-slot-filter");
  const startDateInput = document.getElementById("history-start-date");
  const endDateInput = document.getElementById("history-end-date");
  const historyTableBody = document.getElementById("historyTableBody");
  const loadingIndicator = document.getElementById("history-loading"); // Thêm thẻ span/div để báo loading
  const paginationContainer = document.getElementById("history-pagination"); // Nơi để các nút phân trang

  // Hàm load dữ liệu lịch sử
  async function loadHistory(page = 1) {
    if (loadingIndicator) loadingIndicator.style.display = "block"; // Hiển thị loading
    if (historyTableBody) historyTableBody.innerHTML = ""; // Xóa bảng cũ

    try {
      // 1. Lấy các giá trị filter hiện tại
      const params = new URLSearchParams();
      if (searchInput && searchInput.value)
        params.append("search", searchInput.value);
      if (slotFilter && slotFilter.value)
        params.append("slot_id", slotFilter.value);
      if (startDateInput && startDateInput.value)
        params.append("start_date", startDateInput.value);
      if (endDateInput && endDateInput.value)
        params.append("end_date", endDateInput.value);
      params.append("page", page); // Thêm trang hiện tại
      // params.append('limit', 20); // Có thể thêm giới hạn nếu muốn

      // 2. Gọi API
      const result = await fetchApi(`get_history.php?${params.toString()}`);

      // 3. Hiển thị dữ liệu
      if (result && result.status === "success" && historyTableBody) {
        if (result.data.length === 0) {
          historyTableBody.innerHTML =
            '<tr><td colspan="6" class="text-center">Không tìm thấy dữ liệu phù hợp.</td></tr>';
        } else {
          result.data.forEach((log) => {
            const row = document.createElement("tr");
            const entryTime = new Date(log.timestamp).toLocaleString("vi-VN");
            const actionText =
              log.action === "entry"
                ? '<span class="badge bg-success">Vào</span>'
                : '<span class="badge bg-danger">Ra</span>';

            row.innerHTML = `
                            <td>${log.log_id}</td>
                            <td>${log.uid || "N/A"}</td>
                            <td>${log.user_name || "Không xác định"}</td>
                            <td>${log.slot_name || "N/A"}</td>
                            <td>${entryTime}</td>
                            <td>${actionText}</td>
                        `;
            historyTableBody.appendChild(row);
          });
        }

        // 4. Cập nhật phân trang (nếu API trả về thông tin phân trang)
        // updatePagination(result.pagination); // Cần viết hàm này nếu có phân trang
      } else {
        historyTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Lỗi khi tải dữ liệu: ${
          result.message || "Lỗi không xác định"
        }</td></tr>`;
        showAlert(result.message || "Không thể tải lịch sử.", "danger");
      }
    } catch (error) {
      if (historyTableBody)
        historyTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Lỗi nghiêm trọng: ${error.message}</td></tr>`;
      showAlert(`Lỗi khi tải lịch sử: ${error.message}`, "danger");
    } finally {
      if (loadingIndicator) loadingIndicator.style.display = "none"; // Ẩn loading
    }
  }

  // Gắn sự kiện cho form filter
  if (filterForm) {
    // Dùng 'submit' để bắt cả khi nhấn Enter trong input
    filterForm.addEventListener("submit", (event) => {
      event.preventDefault(); // Ngăn form gửi đi theo cách truyền thống
      loadHistory(1); // Load lại trang đầu tiên khi lọc
    });

    // Hoặc bắt sự kiện 'input'/'change' trên từng element nếu muốn lọc tức thì
    // ['input', 'change'].forEach(evtType => {
    //      searchInput?.addEventListener(evtType, () => loadHistory(1));
    //      slotFilter?.addEventListener(evtType, () => loadHistory(1));
    //      startDateInput?.addEventListener(evtType, () => loadHistory(1));
    //      endDateInput?.addEventListener(evtType, () => loadHistory(1));
    // });
    // Lưu ý: Lọc tức thì với input có thể gây nhiều request, cần debounce/throttle
  }

  // Hàm cập nhật phân trang (ví dụ cơ bản)
  // function updatePagination(pagination) {
  //     if (!paginationContainer || !pagination) return;
  //     paginationContainer.innerHTML = ''; // Clear old pagination
  //     // Logic để tạo các nút Previous, Next, Page Numbers dựa vào pagination.totalPages, pagination.currentPage
  // }

  // Load dữ liệu lần đầu khi trang tải
  loadHistory(1);
}

// --- Logic cho trang Cài đặt ---
function initSettingsPage() {
  const settingsContainer = document.getElementById("settings-container");
  if (!settingsContainer) return;

  console.log("Initializing Settings Page...");

  const generalSettingsForm = document.getElementById("general-settings-form");
  // Các form/button/table khác cho quản lý User, Slot...

  // Xử lý submit form cài đặt chung
  if (generalSettingsForm) {
    generalSettingsForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const submitButton = generalSettingsForm.querySelector(
        'button[type="submit"]'
      );
      submitButton.disabled = true;
      submitButton.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...';

      const formData = new FormData(generalSettingsForm);

      // Log FormData để kiểm tra (chỉ để debug)
      // for (let [key, value] of formData.entries()) {
      //     console.log(key, value);
      // }

      try {
        const result = await fetchApi("update_settings.php", {
          method: "POST",
          body: formData, // FormData sẽ được gửi đúng cách
        });

        if (result && result.status === "success") {
          showAlert("Cập nhật cài đặt thành công!", "success");
        } else {
          showAlert(
            result.message || "Lỗi không xác định khi cập nhật.",
            "danger"
          );
        }
      } catch (error) {
        showAlert(`Lỗi khi lưu cài đặt: ${error.message}`, "danger");
      } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = "Lưu thay đổi";
      }
    });
  }

  // ----- Xử lý cho Quản lý người dùng (Ví dụ) -----
  // Cần có API riêng cho User: add_user.php, update_user.php, delete_user.php, get_users.php

  const userTableBody = document.getElementById("user-table-body");
  const addUserModal = document.getElementById("addUserModal"); // Giả sử có modal Bootstrap
  const addUserForm = document.getElementById("add-user-form");
  // Tương tự cho Edit/Delete modal và form

  // Hàm load danh sách user
  async function loadUsers() {
    if (!userTableBody) return;
    userTableBody.innerHTML =
      '<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm"></span> Đang tải...</td></tr>';
    try {
      // const result = await fetchApi('get_users.php'); // Cần tạo API này
      // if (result && result.status === 'success') {
      //     userTableBody.innerHTML = '';
      //     result.data.forEach(user => {
      //          const row = document.createElement('tr');
      //          row.innerHTML = `
      //              <td>${user.uid}</td>
      //              <td>${user.name}</td>
      //              <td>${user.email || '-'}</td>
      //              <td>
      //                  <button class="btn btn-sm btn-primary btn-edit-user" data-uid="${user.uid}">Sửa</button>
      //                  <button class="btn btn-sm btn-danger btn-delete-user" data-uid="${user.uid}">Xóa</button>
      //              </td>
      //          `;
      //          userTableBody.appendChild(row);
      //     });
      //      // Gắn lại event listener cho các nút Sửa/Xóa mới
      //      attachUserActionListeners();
      // } else {
      //     userTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${result.message || 'Lỗi tải danh sách'}</td></tr>`;
      // }
      userTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-warning">Chức năng User chưa hoàn thiện API</td></tr>`; // Tạm thời
    } catch (error) {
      userTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Lỗi tải danh sách: ${error.message}</td></tr>`;
    }
  }

  // Hàm xử lý submit form thêm user
  if (addUserForm && addUserModal) {
    const modal = bootstrap.Modal.getOrCreateInstance(addUserModal);
    addUserForm.addEventListener("submit", async (event) => {
      event.preventDefault();
      const submitButton = addUserForm.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      submitButton.innerHTML =
        '<span class="spinner-border spinner-border-sm"></span> Đang thêm...';
      const formData = new FormData(addUserForm);

      try {
        // const result = await fetchApi('add_user.php', { method: 'POST', body: formData }); // Cần tạo API này
        // if (result && result.status === 'success') {
        //     showAlert('Thêm người dùng thành công!', 'success');
        //     addUserForm.reset(); // Xóa form
        //     modal.hide(); // Đóng modal
        //     loadUsers(); // Load lại danh sách
        // } else {
        //     showAlert(result.message || 'Lỗi khi thêm người dùng.', 'danger', 'add-user-alert-container'); // Hiển thị lỗi trong modal
        // }
        showAlert(
          "Chức năng User chưa hoàn thiện API.",
          "warning",
          "add-user-alert-container"
        ); // Tạm thời
      } catch (error) {
        showAlert(
          `Lỗi khi thêm: ${error.message}`,
          "danger",
          "add-user-alert-container"
        );
      } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = "Thêm người dùng";
      }
    });
  }

  // Hàm gắn listener cho nút Sửa/Xóa (cần gọi lại sau khi load user)
  function attachUserActionListeners() {
    document.querySelectorAll(".btn-edit-user").forEach((button) => {
      button.addEventListener("click", (event) => {
        const uid = event.target.dataset.uid;
        // Lấy thông tin user cần sửa (gọi API get_user_details.php?uid=...)
        // Mở modal Sửa và điền thông tin
        console.log(`Edit user: ${uid}`); // Tạm thời
        showAlert("Chức năng sửa user chưa được cài đặt.", "info");
      });
    });
    document.querySelectorAll(".btn-delete-user").forEach((button) => {
      button.addEventListener("click", async (event) => {
        const uid = event.target.dataset.uid;
        if (confirm(`Bạn có chắc chắn muốn xóa người dùng với UID: ${uid}?`)) {
          try {
            // const result = await fetchApi('delete_user.php', { method: 'POST', body: JSON.stringify({ uid: uid }) }); // Cần tạo API này
            // if (result && result.status === 'success') {
            //     showAlert('Xóa người dùng thành công!', 'success');
            //     loadUsers(); // Load lại danh sách
            // } else {
            //      showAlert(result.message || 'Lỗi khi xóa người dùng.', 'danger');
            // }
            showAlert("Chức năng xóa user chưa được cài đặt API.", "info"); // Tạm thời
          } catch (error) {
            showAlert(`Lỗi khi xóa: ${error.message}`, "danger");
          }
        }
      });
    });
  }

  // Load danh sách user ban đầu
  // loadUsers();
}

// --- Khởi tạo khi DOM đã sẵn sàng ---
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM fully loaded and parsed");

  // Khởi tạo các phần dựa trên trang hiện tại
  initDashboard();
  initHistoryPage();
  initSettingsPage();

  // Có thể thêm các khởi tạo chung khác ở đây (ví dụ: tooltip của bootstrap)
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
