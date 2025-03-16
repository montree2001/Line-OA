/**
 * reports.js - Fungsi manajemen laporan untuk sistem STUDENT-Prasat
 */

// Objek utama untuk manajemen laporan
const ReportManager = {
    // Inisialisasi fungsi saat halaman dimuat
    init() {
        this.setupEventListeners();
        this.loadDefaultReports();
    },

    // Menyiapkan event listener untuk kontrol laporan
    setupEventListeners() {
        // Filter laporan
        const filterForm = document.getElementById('reportFilterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', this.applyReportFilters.bind(this));
        }

        // Tombol unduh laporan
        const downloadButtons = document.querySelectorAll('.btn-download-report');
        downloadButtons.forEach(button => {
            button.addEventListener('click', this.downloadReport.bind(this));
        });

        // Tombol cetak laporan
        const printButtons = document.querySelectorAll('.btn-print-report');
        printButtons.forEach(button => {
            button.addEventListener('click', this.printReport.bind(this));
        });
    },

    // Memuat laporan default saat halaman dibuka
    loadDefaultReports() {
        this.fetchReportData({
            reportType: 'attendance',
            period: 'monthly',
            classLevel: 'all'
        });
    },

    // Menerapkan filter pada laporan
    applyReportFilters(event) {
        event.preventDefault();

        // Mengambil nilai filter
        const reportType = document.getElementById('reportType').value;
        const period = document.getElementById('reportPeriod').value;
        const classLevel = document.getElementById('classLevel').value;

        // Memuat data laporan dengan filter
        this.fetchReportData({
            reportType,
            period,
            classLevel
        });
    },

    // Mengambil data laporan dari server
    fetchReportData(filters) {
        // Simulasi permintaan AJAX (ganti dengan permintaan aktual ke backend)
        fetch('/api/reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(filters)
        })
        .then(response => response.json())
        .then(data => {
            this.renderReportTable(data);
            this.updateReportCharts(data);
        })
        .catch(error => {
            console.error('Kesalahan mengambil laporan:', error);
            this.showNotification('Gagal memuat laporan', 'error');
        });
    },

    // Merender tabel laporan
    renderReportTable(reportData) {
        const tableBody = document.querySelector('#reportTable tbody');
        
        // Membersihkan isi tabel
        tableBody.innerHTML = '';

        // Mengisi tabel dengan data
        reportData.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.studentName}</td>
                <td>${item.className}</td>
                <td>${item.attendanceRate}%</td>
                <td>
                    <span class="badge ${this.getAttendanceStatusClass(item.attendanceRate)}">
                        ${this.getAttendanceStatus(item.attendanceRate)}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-view" onclick="ReportManager.viewStudentDetails(${item.studentId})">
                            <i class="material-icons">visibility</i>
                        </button>
                        <button class="btn btn-download" onclick="ReportManager.downloadStudentReport(${item.studentId})">
                            <i class="material-icons">download</i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    },

    // Mendapatkan kelas status berdasarkan tingkat kehadiran
    getAttendanceStatusClass(rate) {
        if (rate >= 90) return 'badge-success';
        if (rate >= 80) return 'badge-warning';
        return 'badge-danger';
    },

    // Mendapatkan status kehadiran
    getAttendanceStatus(rate) {
        if (rate >= 90) return 'Baik';
        if (rate >= 80) return 'Perhatian';
        return 'Risiko';
    },

    // Memperbarui grafik laporan
    updateReportCharts(reportData) {
        this.updateAttendanceChart(reportData);
        this.updateRiskLevelChart(reportData);
    },

    // Memperbarui grafik kehadiran
    updateAttendanceChart(reportData) {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        const labels = reportData.map(item => item.className);
        const data = reportData.map(item => item.attendanceRate);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tingkat Kehadiran (%)',
                    data: data,
                    backgroundColor: this.generateChartColors(data.length)
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 60,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    },

    // Memperbarui grafik tingkat risiko
    updateRiskLevelChart(reportData) {
        const ctx = document.getElementById('riskLevelChart');
        if (!ctx) return;

        const riskCategories = {
            good: reportData.filter(item => item.attendanceRate >= 90).length,
            attention: reportData.filter(item => item.attendanceRate >= 80 && item.attendanceRate < 90).length,
            risk: reportData.filter(item => item.attendanceRate < 80).length
        };

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Baik', 'Perhatian', 'Risiko'],
                datasets: [{
                    data: [
                        riskCategories.good, 
                        riskCategories.attention, 
                        riskCategories.risk
                    ],
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.7)',   // Hijau untuk Baik
                        'rgba(255, 152, 0, 0.7)',   // Oranye untuk Perhatian
                        'rgba(244, 67, 54, 0.7)'    // Merah untuk Risiko
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const value = context.parsed;
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ${percentage}%`;
                            }
                        }
                    }
                }
            }
        });
    },

    // Menghasilkan warna untuk grafik
    generateChartColors(count) {
        const colors = [
            'rgba(25, 118, 210, 0.7)',   // Biru
            'rgba(76, 175, 80, 0.7)',    // Hijau
            'rgba(255, 152, 0, 0.7)',    // Oranye
            'rgba(244, 67, 54, 0.7)',    // Merah
            'rgba(156, 39, 176, 0.7)'    // Ungu
        ];

        // Mengulang warna jika jumlah lebih dari warna yang tersedia
        return Array.from({length: count}, (_, i) => colors[i % colors.length]);
    },

    // Melihat detail siswa
    viewStudentDetails(studentId) {
        // Buka modal atau navigasi ke halaman detail siswa
        window.location.href = `/students/details/${studentId}`;
    },

    // Mengunduh laporan siswa
    downloadStudentReport(studentId) {
        fetch(`/api/reports/student/${studentId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/xlsx'
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `laporan_siswa_${studentId}_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
        })
        .catch(error => {
            console.error('Kesalahan mengunduh laporan:', error);
            this.showNotification('Gagal mengunduh laporan', 'error');
        });
    },

    // Mengunduh laporan keseluruhan
    downloadReport(event) {
        const reportType = event.target.dataset.reportType || 'attendance';
        
        fetch(`/api/reports/download?type=${reportType}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/xlsx'
            }
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `laporan_${reportType}_${new Date().toISOString().split('T')[0]}.xlsx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
        })
        .catch(error => {
            console.error('Kesalahan mengunduh laporan:', error);
            this.showNotification('Gagal mengunduh laporan', 'error');
        });
    },

    // Mencetak laporan
    printReport() {
        window.print();
    },

    // Menampilkan notifikasi
    showNotification(message, type = 'info') {
        const notificationContainer = document.getElementById('notificationContainer');
        if (!notificationContainer) return;

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        notificationContainer.appendChild(notification);

        // Hapus notifikasi setelah 3 detik
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
};

// Inisialisasi saat dokumen dimuat
document.addEventListener('DOMContentLoaded', () => {
    ReportManager.init();
});