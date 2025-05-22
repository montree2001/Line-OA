/**
 * enhanced_reports.js - JavaScript mejorado para el dashboard de reportes de asistencia
 * 
 * ระบบน้องสัตบรรณ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// Referencias a los gráficos
let attendanceLineChart;
let attendancePieChart;
let departmentBarChart;
let departmentRadarChart;
let classRankingChart;
let currentStudentId;
let currentPeriod = 'month'; // Periodo predeterminado
let riskDataTable;
let classRankDataTable;
let fullRiskTable;
let classDetailsTable;

// Cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar gráficos
    initializeCharts();
    
    // Configurar los manejadores de eventos
    setupEventListeners();
    
    // Inicializar DataTables
    initializeDataTables();
    
    // Activar la pestaña seleccionada
    activateTab(window.location.hash ? window.location.hash.substring(1) : 'overview');
});

/**
 * Inicializa todos los gráficos del dashboard
 */
function initializeCharts() {
    initializeLineChart();
    initializePieChart();
    initializeDepartmentCharts();
    initializeClassCharts();
}

/**
 * Inicializa el gráfico de línea para mostrar tendencias de asistencia
 */
function initializeLineChart() {
    const ctx = document.getElementById('attendanceLineChart').getContext('2d');
    
    // Preparar datos para el gráfico
    const labels = weeklyTrendsData.map(item => item.date);
    const data = weeklyTrendsData.map(item => item.attendance_rate);
    
    // Definir colores para los días de fin de semana
    const pointBackgroundColors = weeklyTrendsData.map(item => 
        item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
    );
    
    const pointBorderColors = weeklyTrendsData.map(item => 
        item.is_weekend ? 'rgba(200, 200, 200, 0.8)' : 'rgb(40, 167, 69)'
    );
    
    // Crear el gráfico de línea
    attendanceLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: data,
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderColor: 'rgb(40, 167, 69)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: pointBackgroundColors,
                pointBorderColor: pointBorderColors,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...data.filter(val => val > 0)) - 10),
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const isWeekend = weeklyTrendsData[dataIndex]?.is_weekend;
                            
                            if (isWeekend) {
                                return ['วันหยุดสุดสัปดาห์', `อัตราการเข้าแถว: ${context.parsed.y}%`];
                            }
                            return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Inicializa el gráfico circular para mostrar la distribución de estados de asistencia
 */
function initializePieChart() {
    const ctx = document.getElementById('attendancePieChart').getContext('2d');
    
    // Preparar datos para el gráfico circular
    const labels = attendanceStatusData.map(item => item.status);
    const data = attendanceStatusData.map(item => item.percent);
    const colors = attendanceStatusData.map(item => item.color);
    
    // Crear el gráfico circular
    attendancePieChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.parsed}%`;
                        }
                    }
                }
            },
            cutout: '65%',
            animation: {
                animateRotate: true,
                animateScale: true
            }
        }
    });
}

/**
 * Inicializa los gráficos de la pestaña de departamentos
 */
function initializeDepartmentCharts() {
    // Gráfico de barras para departamentos
    const barCtx = document.getElementById('departmentBarChart');
    if (barCtx) {
        const ctx = barCtx.getContext('2d');
        
        // Extraer datos
        const deptNames = departmentData.map(dept => dept.departmentName);
        const attendanceRates = departmentData.map(dept => dept.attendanceRate);
        const studentCounts = departmentData.map(dept => dept.studentCount);
        
        // Crear gráfico de barras para departamentos
        departmentBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: deptNames,
                datasets: [
                    {
                        label: 'อัตราการเข้าแถว (%)',
                        data: attendanceRates,
                        backgroundColor: 'rgba(76, 175, 80, 0.7)',
                        borderColor: 'rgb(76, 175, 80)',
                        borderWidth: 1,
                        barPercentage: 0.6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'จำนวนนักเรียน',
                        data: studentCounts,
                        backgroundColor: 'rgba(33, 150, 243, 0.7)',
                        borderColor: 'rgb(33, 150, 243)',
                        borderWidth: 1,
                        barPercentage: 0.6,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        position: 'left',
                        title: {
                            display: true,
                            text: 'อัตราการเข้าแถว (%)'
                        },
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    y1: {
                        position: 'right',
                        title: {
                            display: true,
                            text: 'จำนวนนักเรียน'
                        },
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'y') {
                                    return label + context.parsed.y + '%';
                                } else {
                                    return label + context.parsed.y + ' คน';
                                }
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico de radar para comparación de departamentos
    const radarCtx = document.getElementById('departmentRadarChart');
    if (radarCtx) {
        const ctx = radarCtx.getContext('2d');
        
        // Crear gráfico de radar para departamentos
        departmentRadarChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: departmentData.map(dept => dept.departmentName),
                datasets: [
                    {
                        label: 'อัตราการเข้าแถว',
                        data: departmentData.map(dept => dept.attendanceRate),
                        fill: true,
                        backgroundColor: 'rgba(76, 175, 80, 0.2)',
                        borderColor: 'rgb(76, 175, 80)',
                        pointBackgroundColor: 'rgb(76, 175, 80)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgb(76, 175, 80)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 50,
                        suggestedMax: 100
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}%`;
                            }
                        }
                    }
                }
            }
        });
    }
}

/**
 * Inicializa los gráficos de la pestaña de clases
 */
function initializeClassCharts() {
    const ctx = document.getElementById('classRankingChart');
    if (ctx) {
        // Obtener los datos de las clases y ordenarlos por tasa de asistencia
        const sortedClasses = [...classRankingData].sort((a, b) => b.attendance_rate - a.attendance_rate);
        const labels = sortedClasses.map(cls => cls.class_name);
        const data = sortedClasses.map(cls => cls.attendance_rate);
        const borderColors = sortedClasses.map(cls => {
            if (cls.rate_class === 'good') return 'rgb(76, 175, 80)';
            if (cls.rate_class === 'warning') return 'rgb(255, 193, 7)';
            return 'rgb(244, 67, 54)';
        });
        const backgroundColors = sortedClasses.map(cls => {
            if (cls.rate_class === 'good') return 'rgba(76, 175, 80, 0.7)';
            if (cls.rate_class === 'warning') return 'rgba(255, 193, 7, 0.7)';
            return 'rgba(244, 67, 54, 0.7)';
        });
        
        // Crear gráfico horizontal para la clasificación de clases
        classRankingChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'อัตราการเข้าแถว (%)',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 1,
                    barPercentage: 0.8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `อัตราการเข้าแถว: ${context.parsed.x}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'อัตราการเข้าแถว (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
}

/**
 * Inicializa las tablas de datos DataTables
 */
function initializeDataTables() {
    // Tabla de estudiantes en riesgo
    if (document.getElementById('risk-students-table')) {
        riskDataTable = $('#risk-students-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { targets: -1, orderable: false }, // Columna de acciones no ordenable
            ],
            order: [[3, 'asc']], // Ordenar por tasa de asistencia ascendente
            drawCallback: setupTableActionListeners
        });
    }
    
    // Tabla de ranking de clases
    if (document.getElementById('class-rank-table')) {
        classRankDataTable = $('#class-rank-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { targets: -1, orderable: false }, // Columna de gráfico no ordenable
            ],
            order: [[4, 'desc']] // Ordenar por tasa de asistencia descendente
        });
    }
    
    // Tabla completa de estudiantes en riesgo
    if (document.getElementById('full-risk-table')) {
        fullRiskTable = $('#full-risk-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { targets: -1, orderable: false }, // Columna de acciones no ordenable
            ],
            order: [[4, 'asc']], // Ordenar por tasa de asistencia ascendente
            drawCallback: setupTableActionListeners
        });
    }
    
    // Tabla de detalles de clases
    if (document.getElementById('class-details-table')) {
        classDetailsTable = $('#class-details-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json',
            },
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            columnDefs: [
                { targets: -1, orderable: false }, // Columna de gráfico no ordenable
            ],
            order: [[3, 'desc']] // Ordenar por tasa de asistencia descendente
        });
    }
    
    // Ajustar responsividad de las tablas
    $(window).resize(function() {
        adjustDataTablesResponsiveness();
    });
    
    // Llamar inicialmente
    adjustDataTablesResponsiveness();
}

/**
 * Configura los listeners para los botones de acción en las tablas
 */
function setupTableActionListeners() {
    // Botones de ver detalles
    document.querySelectorAll('.action-button.view').forEach(button => {
        button.removeEventListener('click', viewStudentDetailHandler);
        button.addEventListener('click', viewStudentDetailHandler);
    });
    
    // Botones de enviar mensaje
    document.querySelectorAll('.action-button.message').forEach(button => {
        button.removeEventListener('click', notifyParentHandler);
        button.addEventListener('click', notifyParentHandler);
    });
    
    // Enlaces a detalles de estudiante
    document.querySelectorAll('.student-link').forEach(link => {
        link.removeEventListener('click', viewStudentDetailFromLinkHandler);
        link.addEventListener('click', viewStudentDetailFromLinkHandler);
    });
}

// Manejadores de eventos para la tabla
function viewStudentDetailHandler(e) {
    const studentId = this.getAttribute('data-student-id');
    viewStudentDetail(studentId);
}

function notifyParentHandler(e) {
    const studentId = this.getAttribute('data-student-id');
    notifyParent(studentId);
}

function viewStudentDetailFromLinkHandler(e) {
    e.preventDefault();
    const studentId = this.getAttribute('data-student-id');
    viewStudentDetail(studentId);
}

/**
 * Ajusta la responsividad de las tablas DataTables
 */
function adjustDataTablesResponsiveness() {
    const tables = [riskDataTable, classRankDataTable, fullRiskTable, classDetailsTable];
    tables.forEach(table => {
        if (table) {
            table.columns.adjust().responsive.recalc();
        }
    });
}

/**
 * Configura todos los manejadores de eventos para el dashboard
 */
function setupEventListeners() {
    // Manejador para alternar el panel de filtros
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    if (toggleFiltersBtn) {
        toggleFiltersBtn.addEventListener('click', function() {
            const filtersPanel = document.getElementById('advancedFilters');
            filtersPanel.classList.toggle('active');
            
            // Alternar ícono
            const icon = this.querySelector('.filter-icon');
            if (icon) {
                if (filtersPanel.classList.contains('active')) {
                    icon.textContent = 'keyboard_arrow_up';
                } else {
                    icon.textContent = 'keyboard_arrow_down';
                }
            }
        });
    }
    
    // Pestañas de navegación
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            activateTab(tabId);
            
            // Actualizar URL hash para mantener la pestaña al recargar
            window.location.hash = tabId;
        });
    });
    
    // Botones de periodo del gráfico de línea
    document.querySelectorAll('.chart-actions .chart-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Quitar clase active de todas las pestañas
            document.querySelectorAll('.chart-actions .chart-tab').forEach(t => t.classList.remove('active'));
            // Añadir clase active a la pestaña seleccionada
            this.classList.add('active');
            
            // Actualizar gráfico con el periodo seleccionado
            const period = this.getAttribute('data-period');
            updateAttendanceChart(period);
        });
    });
    
    // Botones de filtro de nivel de clase
    document.querySelectorAll('.card-actions .chart-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Quitar clase active de todas las pestañas
            document.querySelectorAll('.card-actions .chart-tab').forEach(t => t.classList.remove('active'));
            // Añadir clase active a la pestaña seleccionada
            this.classList.add('active');
            
            // Filtrar tabla por nivel
            const level = this.getAttribute('data-level');
            filterClassTable(level);
        });
    });
    
    // Filtros de nivel en la pestaña de clases
    document.querySelectorAll('[data-class-level]').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('[data-class-level]').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const level = this.getAttribute('data-class-level');
            filterClassDetailsTable(level);
            
            // También actualizar el gráfico de clasificación de clases si está visible
            updateClassRankingChart(level);
        });
    });
    
    // Selector de periodo
    const periodSelector = document.getElementById('period-selector');
    if (periodSelector) {
        periodSelector.addEventListener('change', changePeriod);
    }
    
    // Botones de periodo en filtros avanzados
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const period = this.getAttribute('data-period');
            if (periodSelector) {
                periodSelector.value = period;
            }
            changePeriod();
        });
    });
    
    // Selector de departamento
    const departmentSelector = document.getElementById('department-selector');
    if (departmentSelector) {
        departmentSelector.addEventListener('change', changeDepartment);
    }
    
    // Selector de departamento en filtros avanzados
    const filterDepartment = document.getElementById('filter-department');
    if (filterDepartment) {
        filterDepartment.addEventListener('change', function() {
            if (departmentSelector) {
                departmentSelector.value = this.value;
            }
            changeDepartment();
        });
    }
    
    // Botón de aplicar rango de fechas
    const applyDateRangeBtn = document.getElementById('applyDateRangeBtn');
    if (applyDateRangeBtn) {
        applyDateRangeBtn.addEventListener('click', applyDateRange);
    }
    
    // Botón de descargar reporte
    const downloadButton = document.getElementById('downloadReportBtn');
    if (downloadButton) {
        downloadButton.addEventListener('click', downloadReport);
    }
    
    // Botón de imprimir reporte
    const printButton = document.getElementById('printReportBtn');
    if (printButton) {
        printButton.addEventListener('click', printReport);
    }
    
    // Botón de notificar a todos los estudiantes en riesgo
    const notifyAllButton = document.getElementById('notifyAllBtn');
    if (notifyAllButton) {
        notifyAllButton.addEventListener('click', confirmNotifyAllRiskStudents);
    }
    
    const notifyAllRiskBtn = document.getElementById('notifyAllRiskBtn');
    if (notifyAllRiskBtn) {
        notifyAllRiskBtn.addEventListener('click', confirmNotifyAllRiskStudents);
    }
    
    // Botones de notificación específicos
    const notifyRiskGroupBtn = document.getElementById('notifyRiskGroupBtn');
    if (notifyRiskGroupBtn) {
        notifyRiskGroupBtn.addEventListener('click', function() {
            confirmNotifyGroup('risk');
        });
    }
    
    const notifyFailedBtn = document.getElementById('notifyFailedBtn');
    if (notifyFailedBtn) {
        notifyFailedBtn.addEventListener('click', function() {
            confirmNotifyGroup('failed');
        });
    }
    
    // Manejadores de modal de detalles de estudiante
    const closeStudentModal = document.getElementById('closeStudentModal');
    if (closeStudentModal) {
        closeStudentModal.addEventListener('click', function() {
            document.getElementById('studentDetailModal').style.display = 'none';
        });
    }
    
    // Manejadores de modal de notificación
    const closeNotificationModal = document.getElementById('closeNotificationModal');
    if (closeNotificationModal) {
        closeNotificationModal.addEventListener('click', function() {
            document.getElementById('notificationModal').style.display = 'none';
        });
    }
    
    const cancelNotification = document.getElementById('cancelNotification');
    if (cancelNotification) {
        cancelNotification.addEventListener('click', function() {
            document.getElementById('notificationModal').style.display = 'none';
        });
    }
    
    const sendNotificationBtn = document.getElementById('sendNotification');
    if (sendNotificationBtn) {
        sendNotificationBtn.addEventListener('click', sendNotification);
    }
    
    // Selector de plantilla de notificación
    const notificationTemplate = document.getElementById('notification-template');
    if (notificationTemplate) {
        notificationTemplate.addEventListener('change', updateNotificationContent);
    }
    
    // Selector de plantilla en panel de notificaciones
    const notificationTemplateSelect = document.getElementById('notification-template-select');
    if (notificationTemplateSelect) {
        notificationTemplateSelect.addEventListener('change', updateNotificationMessage);
    }
    
    // Manejadores de modal de rango de fechas
    const closeDateRangeModal = document.getElementById('closeDateRangeModal');
    if (closeDateRangeModal) {
        closeDateRangeModal.addEventListener('click', function() {
            document.getElementById('dateRangeModal').style.display = 'none';
        });
    }
    
    const cancelDateRange = document.getElementById('cancelDateRange');
    if (cancelDateRange) {
        cancelDateRange.addEventListener('click', function() {
            document.getElementById('dateRangeModal').style.display = 'none';
        });
    }
    
    const applyDateRange = document.getElementById('applyDateRange');
    if (applyDateRange) {
        applyDateRange.addEventListener('click', applyDateRange);
    }
    
    // Cerrar modales al hacer clic en el fondo
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    };
    
    // Búsqueda de estudiantes en la tabla de riesgo completa
    const riskStudentSearch = document.getElementById('risk-student-search');
    if (riskStudentSearch && fullRiskTable) {
        riskStudentSearch.addEventListener('keyup', function() {
            fullRiskTable.search(this.value).draw();
        });
    }
    
    // Búsqueda de estudiantes en la tabla principal
    const studentSearch = document.getElementById('student-search');
    if (studentSearch && riskDataTable) {
        studentSearch.addEventListener('keyup', function() {
            riskDataTable.search(this.value).draw();
        });
    }
}

/**
 * Activa una pestaña específica por su ID
 * @param {string} tabId - ID de la pestaña a activar
 */
function activateTab(tabId) {
    // Ocultar todas las pestañas
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Desactivar todos los botones de pestaña
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Mostrar la pestaña seleccionada
    const selectedTab = document.getElementById('tab-' + tabId);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Activar el botón correspondiente
    const selectedBtn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (selectedBtn) {
        selectedBtn.classList.add('active');
    }
    
    // Reajustar tablas para la nueva pestaña
    if (tabId === 'riskStudents' && fullRiskTable) {
        fullRiskTable.columns.adjust().responsive.recalc();
    } else if (tabId === 'classes' && classDetailsTable) {
        classDetailsTable.columns.adjust().responsive.recalc();
        
        // Asegurarse de que el gráfico se renderice correctamente
        if (classRankingChart) {
            setTimeout(() => {
                classRankingChart.resize();
                classRankingChart.update();
            }, 100);
        }
    } else if (tabId === 'departments' && departmentBarChart && departmentRadarChart) {
        setTimeout(() => {
            departmentBarChart.resize();
            departmentBarChart.update();
            departmentRadarChart.resize();
            departmentRadarChart.update();
        }, 100);
    }
}

/**
 * Actualiza el gráfico de tendencias de asistencia según el periodo seleccionado
 * @param {string} period - Periodo de tiempo ('week', 'month', 'semester')
 */
function updateAttendanceChart(period) {
    if (period === currentPeriod) return;
    currentPeriod = period;
    
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX para obtener los datos
    // fetch(`api/reports.php?action=weekly_trends&period=${period}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         updateChartWithData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching trend data:', error);
    //         hideLoading();
    //     });
    
    // Simular la obtención de datos con setTimeout
    setTimeout(() => {
        let labels = [];
        let data = [];
        let colors = [];
        
        switch (period) {
            case 'week':
                // Usar datos existentes
                labels = weeklyTrendsData.map(item => item.date);
                data = weeklyTrendsData.map(item => item.attendance_rate);
                colors = weeklyTrendsData.map(item => 
                    item.is_weekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)'
                );
                break;
                
            case 'month':
                // Simular datos mensuales
                const daysInMonth = 30;
                labels = Array.from({length: daysInMonth}, (_, i) => `${i+1}`);
                data = Array.from({length: daysInMonth}, (_, i) => {
                    // Fines de semana con tasas más bajas
                    const isWeekend = (i + 1) % 7 === 0 || (i + 1) % 7 === 6;
                    return isWeekend ? Math.floor(10 + Math.random() * 20) : Math.floor(85 + Math.random() * 10);
                });
                colors = Array.from({length: daysInMonth}, (_, i) => {
                    const isWeekend = (i + 1) % 7 === 0 || (i + 1) % 7 === 6;
                    return isWeekend ? 'rgba(200, 200, 200, 0.5)' : 'rgb(40, 167, 69)';
                });
                break;
                
            case 'semester':
                // Simular datos semestrales
                labels = ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
                data = [94.5, 93.8, 92.5, 93.2, 94.1, 94.8];
                colors = Array(6).fill('rgb(40, 167, 69)');
                break;
        }
        
        // Actualizar el gráfico con los nuevos datos
        if (attendanceLineChart) {
            attendanceLineChart.data.labels = labels;
            attendanceLineChart.data.datasets[0].data = data;
            attendanceLineChart.data.datasets[0].pointBackgroundColor = colors;
            attendanceLineChart.data.datasets[0].pointBorderColor = colors;
            
            // Ajustar escala Y para mejorar visualización
            const minValue = Math.min(...data.filter(val => val > 0));
            attendanceLineChart.options.scales.y.min = Math.max(0, minValue - 10);
            
            attendanceLineChart.update();
        }
        
        hideLoading();
    }, 800);
}

/**
 * Filtra la tabla de clasificación de clases por nivel educativo
 * @param {string} level - Nivel a filtrar ('all', 'high', 'middle')
 */
function filterClassTable(level) {
    if (classRankDataTable) {
        if (level === 'all') {
            classRankDataTable.column(0).search('').draw();
        } else {
            classRankDataTable.column(0).search(`data-level="${level}"`).draw();
        }
    }
}

/**
 * Filtra la tabla de detalles de clases por nivel educativo
 * @param {string} level - Nivel a filtrar ('all', 'high', 'middle')
 */
function filterClassDetailsTable(level) {
    if (classDetailsTable) {
        if (level === 'all') {
            classDetailsTable.search('').draw();
        } else {
            // Buscar clases que comiencen con ปวช para middle o ปวส para high
            const searchTerm = level === 'middle' ? 'ปวช' : 'ปวส';
            classDetailsTable.search(searchTerm).draw();
        }
    }
}

/**
 * Actualiza el gráfico de ranking de clases según el nivel seleccionado
 * @param {string} level - Nivel a filtrar ('all', 'high', 'middle')
 */
function updateClassRankingChart(level) {
    if (!classRankingChart) return;
    
    // Filtrar datos según el nivel
    let filteredClasses = [...classRankingData];
    if (level !== 'all') {
        filteredClasses = classRankingData.filter(cls => {
            const isMiddle = cls.class_name.startsWith('ปวช');
            return (level === 'middle' && isMiddle) || (level === 'high' && !isMiddle);
        });
    }
    
    // Ordenar por tasa de asistencia
    filteredClasses.sort((a, b) => b.attendance_rate - a.attendance_rate);
    
    // Actualizar el gráfico
    classRankingChart.data.labels = filteredClasses.map(cls => cls.class_name);
    classRankingChart.data.datasets[0].data = filteredClasses.map(cls => cls.attendance_rate);
    classRankingChart.data.datasets[0].backgroundColor = filteredClasses.map(cls => {
        if (cls.rate_class === 'good') return 'rgba(76, 175, 80, 0.7)';
        if (cls.rate_class === 'warning') return 'rgba(255, 193, 7, 0.7)';
        return 'rgba(244, 67, 54, 0.7)';
    });
    classRankingChart.data.datasets[0].borderColor = filteredClasses.map(cls => {
        if (cls.rate_class === 'good') return 'rgb(76, 175, 80)';
        if (cls.rate_class === 'warning') return 'rgb(255, 193, 7)';
        return 'rgb(244, 67, 54)';
    });
    
    classRankingChart.update();
}

/**
 * Maneja el cambio de periodo desde el selector
 */
function changePeriod() {
    const periodSelector = document.getElementById('period-selector');
    const period = periodSelector ? periodSelector.value : 'month';
    
    // Si es personalizado, mostrar selector de fechas
    if (period === 'custom') {
        showDateRangeSelector();
        return;
    }
    
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch(`api/reports.php?action=overview&period=${period}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         updateDashboardData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching period data:', error);
    //         hideLoading();
    //     });
    
    // Simular obtención de datos
    setTimeout(() => {
        hideLoading();
        
        // Actualizar botones de periodo en filtros avanzados
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-period') === period) {
                btn.classList.add('active');
            }
        });
        
        // Notificar al usuario
        let periodText = '';
        switch (period) {
            case 'day': periodText = 'วันนี้'; break;
            case 'yesterday': periodText = 'เมื่อวาน'; break;
            case 'week': periodText = 'สัปดาห์นี้'; break;
            case 'month': periodText = 'เดือนนี้'; break;
            case 'semester': periodText = `ภาคเรียนที่ ${academicYearData.semester}/${academicYearData.thai_year}`; break;
        }
        
        alert(`เปลี่ยนการแสดงผลเป็นช่วง: ${periodText} เรียบร้อยแล้ว`);
    }, 800);
}

/**
 * Maneja el cambio de departamento desde el selector
 */
function changeDepartment() {
    const departmentSelector = document.getElementById('department-selector');
    const departmentId = departmentSelector ? departmentSelector.value : 'all';
    
    // Actualizar selector en filtros avanzados
    const filterDepartment = document.getElementById('filter-department');
    if (filterDepartment && filterDepartment.value !== departmentId) {
        filterDepartment.value = departmentId;
    }
    
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch(`api/reports.php?action=overview&department_id=${departmentId}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         updateDashboardData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching department data:', error);
    //         hideLoading();
    //     });
    
    // Simular obtención de datos
    setTimeout(() => {
        hideLoading();
        
        const departmentText = departmentSelector && departmentSelector.options[departmentSelector.selectedIndex] 
            ? departmentSelector.options[departmentSelector.selectedIndex].text 
            : 'ทุกแผนก';
            
        alert(`เปลี่ยนการแสดงผลเป็นแผนก: ${departmentText} เรียบร้อยแล้ว`);
    }, 800);
}

/**
 * Muestra el selector de rango de fechas
 */
function showDateRangeSelector() {
    // Configurar fechas iniciales
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    
    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    if (startDate) startDate.valueAsDate = firstDayOfMonth;
    if (endDate) endDate.valueAsDate = today;
    
    // Mostrar el modal
    const dateRangeModal = document.getElementById('dateRangeModal');
    if (dateRangeModal) {
        dateRangeModal.style.display = 'block';
    }
}

/**
 * Aplica el rango de fechas seleccionado
 */
function applyDateRange() {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    
    if (!startDateInput || !endDateInput) return;
    
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    
    if (!startDate || !endDate) {
        alert('กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด');
        return;
    }
    
    if (new Date(startDate) > new Date(endDate)) {
        alert('วันที่เริ่มต้นต้องมาก่อนวันที่สิ้นสุด');
        return;
    }
    
    // Cerrar el modal
    const dateRangeModal = document.getElementById('dateRangeModal');
    if (dateRangeModal) {
        dateRangeModal.style.display = 'none';
    }
    
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch(`api/reports.php?action=overview&period=custom&start_date=${startDate}&end_date=${endDate}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         updateDashboardData(data);
    //         hideLoading();
    //     })
    //     .catch(error => {
    //         console.error('Error fetching custom date range:', error);
    //         hideLoading();
    //     });
    
    // Simular obtención de datos
    setTimeout(() => {
        hideLoading();
        
        // Mostrar el rango seleccionado
        const formatDate = (dateStr) => {
            const d = new Date(dateStr);
            return d.toLocaleDateString('th-TH', {year: 'numeric', month: 'long', day: 'numeric'});
        };
        
        alert(`เปลี่ยนการแสดงผลเป็นช่วงวันที่ ${formatDate(startDate)} ถึง ${formatDate(endDate)} เรียบร้อยแล้ว`);
        
        // Actualizar selector de periodo
        const periodSelector = document.getElementById('period-selector');
        if (periodSelector) {
            periodSelector.value = 'custom';
        }
        
        // Actualizar botones de periodo en filtros avanzados
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    }, 800);
}

/**
 * Descarga el reporte actual
 */
function downloadReport() {
    const periodSelector = document.getElementById('period-selector');
    const departmentSelector = document.getElementById('department-selector');
    
    const period = periodSelector ? periodSelector.value : 'month';
    const departmentId = departmentSelector ? departmentSelector.value : 'all';
    
    showLoading();
    
    // En una implementación real, aquí se generaría la URL para descargar
    // window.open(`api/reports.php?action=export_report&format=excel&period=${period}&department_id=${departmentId}`, '_blank');
    
    // Simular descarga
    setTimeout(() => {
        hideLoading();
        
        let periodText = '';
        switch (period) {
            case 'day': periodText = 'วันนี้'; break;
            case 'yesterday': periodText = 'เมื่อวาน'; break;
            case 'week': periodText = 'สัปดาห์นี้'; break;
            case 'month': periodText = 'เดือนนี้'; break;
            case 'semester': periodText = `ภาคเรียนที่ ${academicYearData.semester}/${academicYearData.thai_year}`; break;
            case 'custom': periodText = 'ช่วงวันที่ที่กำหนด'; break;
        }
        
        const departmentText = departmentSelector && departmentSelector.options[departmentSelector.selectedIndex] 
            ? departmentSelector.options[departmentSelector.selectedIndex].text 
            : 'ทุกแผนก';
        
        alert(`เริ่มดาวน์โหลดรายงานสำหรับช่วง: ${periodText} แผนก: ${departmentText}`);
    }, 1000);
}

/**
 * Imprime el reporte actual
 */
function printReport() {
    window.print();
}

/**
 * Muestra los detalles de un estudiante
 * @param {number} studentId - ID del estudiante
 */
function viewStudentDetail(studentId) {
    currentStudentId = studentId;
    
    // Mostrar el modal
    const studentDetailModal = document.getElementById('studentDetailModal');
    if (studentDetailModal) {
        studentDetailModal.style.display = 'block';
    }
    
    // Mostrar estado de carga
    const studentDetailContent = document.getElementById('student-detail-content');
    if (studentDetailContent) {
        studentDetailContent.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch(`api/reports.php?action=student_details&student_id=${studentId}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         displayStudentDetails(data);
    //     })
    //     .catch(error => {
    //         console.error('Error fetching student details:', error);
    //         if (studentDetailContent) {
    //             studentDetailContent.innerHTML = '<div class="error">เกิดข้อผิดพลาดในการโหลดข้อมูลนักเรียน</div>';
    //         }
    //     });
    
    // Simular obtención de datos
    setTimeout(() => {
        // Buscar los datos del estudiante en la tabla
        let studentData;
        
        // Buscar en la tabla principal
        let studentRow = document.querySelector(`#risk-students-table tr[data-student-id="${studentId}"]`);
        if (!studentRow) {
            // Si no está en la tabla principal, buscar en la tabla completa
            studentRow = document.querySelector(`#full-risk-table tr[data-student-id="${studentId}"]`);
        }
        
        if (studentRow) {
            const nameElement = studentRow.querySelector('.student-detail a');
            const codeElement = studentRow.querySelector('.student-detail p');
            const classElement = studentRow.querySelector('td:nth-child(2)');
            const rateElement = studentRow.querySelector('.attendance-rate');
            const advisorElement = studentRow.querySelector('td:nth-child(3)');
            
            studentData = {
                id: studentId,
                name: nameElement ? nameElement.textContent.trim() : `นักเรียนรหัส ${studentId}`,
                code: codeElement ? codeElement.textContent.replace('รหัส: ', '').trim() : '6731XXXXXX',
                class: classElement ? classElement.textContent.trim() : 'ไม่ระบุ',
                advisorName: advisorElement ? advisorElement.textContent.trim() : 'ไม่ระบุ',
                attendanceRate: rateElement ? parseFloat(rateElement.textContent) : 75.0,
                attendance: [
                    { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' },
                    { date: '7 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:50', remarks: '-' },
                    { date: '8 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' },
                    { date: '9 พ.ค. 2568', status: 'มาสาย', statusClass: 'warning', time: '08:32', remarks: 'รถติด' },
                    { date: '10 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:40', remarks: '-' },
                    { date: '11 พ.ค. 2568', status: 'ลา', statusClass: 'info', time: '-', remarks: 'ป่วย' },
                    { date: '12 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' }
                ],
                monthlyTrend: {
                    labels: ['มี.ค.', 'เม.ย.', 'พ.ค.'],
                    rates: [60, 65, rateElement ? parseFloat(rateElement.textContent) : 65.8]
                },
                notifications: [
                    { date: '28 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'อ.' + (advisorElement ? advisorElement.textContent.trim() : 'ที่ปรึกษา'), status: 'ส่งสำเร็จ' },
                    { date: '15 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'ฝ่ายกิจการนักเรียน', status: 'ส่งสำเร็จ' }
                ]
            };
        } else {
            // Datos de ejemplo si no se encuentra
            studentData = {
                id: studentId,
                name: 'นักเรียนรหัส ' + studentId,
                code: '67319010001',
                class: 'ปวช.1/1',
                advisorName: 'อาจารย์ที่ปรึกษา',
                attendanceRate: 65.8,
                attendance: [
                    { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:45', remarks: '-' },
                    { date: '7 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' },
                    { date: '8 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:50', remarks: '-' },
                    { date: '9 พ.ค. 2568', status: 'มา', statusClass: 'success', time: '07:42', remarks: '-' },
                    { date: '10 พ.ค. 2568', status: 'ขาด', statusClass: 'danger', time: '-', remarks: 'ไม่มาโรงเรียน' }
                ],
                monthlyTrend: {
                    labels: ['มี.ค.', 'เม.ย.', 'พ.ค.'],
                    rates: [60, 65, 65.8]
                },
                notifications: [
                    { date: '28 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'อาจารย์ที่ปรึกษา', status: 'ส่งสำเร็จ' },
                    { date: '15 เม.ย. 2568', type: 'แจ้งเตือนความเสี่ยง', sender: 'ฝ่ายกิจการนักเรียน', status: 'ส่งสำเร็จ' }
                ]
            };
        }
        
        // Mostrar los detalles
        displayStudentDetails(studentData);
    }, 800);
}

/**
 * Muestra los detalles de un estudiante en el modal
 * @param {Object} studentData - Datos del estudiante
 */
function displayStudentDetails(studentData) {
    // Actualizar el título del modal
    const modalTitle = document.getElementById('modal-student-name');
    if (modalTitle) {
        modalTitle.textContent = 'ข้อมูลการเข้าแถว - ' + studentData.name;
    }
    
    // Determinar la clase según la tasa de asistencia
    let rateClass = 'text-success';
    let statusText = 'ปกติ';
    if (studentData.attendanceRate < 80 && studentData.attendanceRate >= 70) {
        rateClass = 'text-warning';
        statusText = 'เสี่ยงตกกิจกรรม';
    } else if (studentData.attendanceRate < 70) {
        rateClass = 'text-danger';
        statusText = 'ตกกิจกรรม';
    }
    
    // Calcular estadísticas
    const presentDays = studentData.attendance.filter(day => day.status === 'มา' || day.status === 'มาสาย').length;
    const absentDays = studentData.attendance.filter(day => day.status === 'ขาด').length;
    const leaveDays = studentData.attendance.filter(day => day.status === 'ลา').length;
    const totalDays = studentData.attendance.length;
    
    // Generar HTML
    let html = `
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar">${studentData.name.charAt(0)}</div>
                <div class="student-profile-info">
                    <h3>${studentData.name}</h3>
                    <p>รหัสนักเรียน: ${studentData.code}</p>
                    <p>ชั้น ${studentData.class}</p>
                    <p>ครูที่ปรึกษา: ${studentData.advisorName}</p>
                    <p>สถานะการเข้าแถว: <span class="${rateClass}">${statusText} (${studentData.attendanceRate}%)</span></p>
                </div>
            </div>
            
            <div class="student-attendance-summary">
                <h4>สรุปการเข้าแถวประจำเดือน${academicYearData.current_month} ${academicYearData.current_year}</h4>
                <div class="attendance-summary-grid">
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${presentDays}</div>
                        <div class="attendance-stat-label">วันที่เข้าแถว</div>
                    </div>
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${absentDays}</div>
                        <div class="attendance-stat-label">วันที่ขาดแถว</div>
                    </div>
                    <div class="attendance-stat">
                        <div class="attendance-stat-value">${totalDays}</div>
                        <div class="attendance-stat-label">วันทั้งหมด</div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-history">
                <h4>ประวัติการเข้าแถวรายวัน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                                <th>หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    // Añadir historial de asistencia
    studentData.attendance.forEach(day => {
        html += `
            <tr>
                <td>${day.date}</td>
                <td><span class="status-badge ${day.statusClass}">${day.status}</span></td>
                <td>${day.time}</td>
                <td>${day.remarks}</td>
            </tr>`;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="attendance-chart">
                <h4>แนวโน้มการเข้าแถวรายเดือน</h4>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="studentMonthlyChart"></canvas>
                </div>
            </div>
            
            <div class="notification-history">
                <h4>ประวัติการแจ้งเตือนผู้ปกครอง</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>ผู้ส่ง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>`;
    
    // Añadir historial de notificaciones
    studentData.notifications.forEach(notification => {
        html += `
            <tr>
                <td>${notification.date}</td>
                <td>${notification.type}</td>
                <td>${notification.sender}</td>
                <td><span class="status-badge success">${notification.status}</span></td>
            </tr>`;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="btn-cancel" onclick="document.getElementById('studentDetailModal').style.display='none'">ปิด</button>
                <button class="btn-send" onclick="notifyParent(${studentData.id})">
                    <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                </button>
                <button class="btn-primary" onclick="window.location.href='student_details.php?id=${studentData.id}'">
                    <span class="material-icons">visibility</span> ดูข้อมูลเพิ่มเติม
                </button>
            </div>
        </div>
    `;
    
    // Actualizar el contenido del modal
    const studentDetailContent = document.getElementById('student-detail-content');
    if (studentDetailContent) {
        studentDetailContent.innerHTML = html;
    }
    
    // Crear el gráfico de tendencia mensual
    createStudentMonthlyChart(studentData.monthlyTrend);
}

/**
 * Crea el gráfico de tendencia mensual para un estudiante
 * @param {Object} trendData - Datos de tendencia (labels y rates)
 */
function createStudentMonthlyChart(trendData) {
    const ctx = document.getElementById('studentMonthlyChart');
    if (!ctx) return;
    
    // Determinar color según la tasa de asistencia
    const lastRate = trendData.rates[trendData.rates.length - 1];
    const chartColor = lastRate >= 70 ? 
        (lastRate >= 80 ? '#28a745' : '#ffc107') : '#dc3545';
    
    // Crear el gráfico
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: trendData.labels,
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: trendData.rates,
                backgroundColor: `${chartColor}20`,
                borderColor: chartColor,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: chartColor,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    min: Math.max(0, Math.min(...trendData.rates) - 10),
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Prepara para enviar una notificación a un padre
 * @param {number} studentId - ID del estudiante
 */
function notifyParent(studentId) {
    currentStudentId = studentId;
    
    // Mostrar el modal de notificación
    const notificationModal = document.getElementById('notificationModal');
    if (notificationModal) {
        notificationModal.style.display = 'block';
    }
    
    // Actualizar el contenido de la plantilla
    updateNotificationContent();
}

/**
 * Actualiza el contenido de la notificación según la plantilla seleccionada
 */
function updateNotificationContent() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    
    if (!templateSelect || !contentField) return;
    
    const template = templateSelect.value;
    
    // Buscar datos del estudiante
    let studentName = "นักเรียน";
    let className = "";
    let advisorName = "";
    
    // Buscar en las tablas
    const studentRow = document.querySelector(`[data-student-id="${currentStudentId}"]`);
    if (studentRow) {
        const nameElement = studentRow.querySelector('.student-detail a');
        const classElement = studentRow.querySelector('td:nth-child(2)');
        const advisorElement = studentRow.querySelector('td:nth-child(3)');
        
        if (nameElement) studentName = nameElement.textContent.trim();
        if (classElement) className = classElement.textContent.trim();
        if (advisorElement) advisorName = advisorElement.textContent.trim();
    }
    
    // Plantillas de ejemplo
    let message = '';
    switch (template) {
        case 'risk_alert':
            message = `เรียน ผู้ปกครองของ ${studentName}

ทางวิทยาลัยขอแจ้งว่า ${studentName} นักเรียนชั้น ${className} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 70% ซึ่งต่ำกว่าเกณฑ์ที่กำหนด (80%)

กรุณาติดต่อครูที่ปรึกษา ${advisorName} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'absence_alert':
            message = `เรียน ผู้ปกครองของ ${studentName}

ทางวิทยาลัยขอแจ้งว่า ${studentName} นักเรียนชั้น ${className} ไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้

กรุณาติดต่อครูที่ปรึกษา ${advisorName} หากมีข้อสงสัย

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'monthly_report':
            message = `เรียน ผู้ปกครองของ ${studentName}

รายงานสรุปการเข้าแถวประจำเดือน${academicYearData.current_month} ${academicYearData.current_year}

จำนวนวันเข้าแถว: 15 วัน
จำนวนวันขาด: 5 วัน
อัตราการเข้าแถว: 75%
สถานะ: เสี่ยงไม่ผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษา ${advisorName} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            message = '';
            break;
    }
    
    contentField.value = message;
}

/**
 * Actualiza el mensaje de notificación en el panel de riesgo
 */
function updateNotificationMessage() {
    const templateSelect = document.getElementById('notification-template-select');
    const messageField = document.getElementById('notification-message');
    
    if (!templateSelect || !messageField) return;
    
    const template = templateSelect.value;
    
    // Plantillas de ejemplo
    let message = '';
    switch (template) {
        case 'risk_alert':
            message = `เรียน ผู้ปกครองของ [ชื่อนักเรียน]

ทางวิทยาลัยขอแจ้งว่าบุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันมีอัตราการเข้าแถวเพียง [อัตราการเข้าแถว]% ซึ่งต่ำกว่าเกณฑ์ที่กำหนดไว้ (80%)

กรุณาติดต่อครูที่ปรึกษา [ชื่อครูที่ปรึกษา] โทร. [เบอร์โทรครู] เพื่อหาแนวทางแก้ไข

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'absent_alert':
            message = `เรียน ผู้ปกครองของ [ชื่อนักเรียน]

ทางวิทยาลัยขอแจ้งว่า [ชื่อนักเรียน] นักเรียนชั้น [ชั้นเรียน] ไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้ [วันที่]

กรุณาติดต่อครูที่ปรึกษา [ชื่อครูที่ปรึกษา] โทร. [เบอร์โทรครู] หากมีข้อสงสัย

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'monthly_report':
            message = `เรียน ผู้ปกครองของ [ชื่อนักเรียน]

รายงานสรุปการเข้าแถวประจำเดือน[เดือน] [ปี]

จำนวนวันเข้าแถว: [จำนวนวันเข้าแถว] วัน
จำนวนวันขาด: [จำนวนวันขาด] วัน
อัตราการเข้าแถว: [อัตราการเข้าแถว]%
สถานะ: [สถานะการเข้าแถว]

กรุณาติดต่อครูที่ปรึกษา [ชื่อครูที่ปรึกษา] โทร. [เบอร์โทรครู] หากมีข้อสงสัย

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            break;
        case 'custom':
            message = '';
            break;
    }
    
    messageField.value = message;
}

/**
 * Envía la notificación al padre
 */
function sendNotification() {
    const templateSelect = document.getElementById('notification-template');
    const contentField = document.getElementById('notification-content');
    
    if (!templateSelect || !contentField) return;
    
    const template = templateSelect.value;
    const content = contentField.value;
    
    if (!content.trim()) {
        alert('กรุณากรอกข้อความแจ้งเตือน');
        return;
    }
    
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch('api/reports.php?action=send_notification', {
    //     method: 'POST',
    //     body: JSON.stringify({
    //         student_id: currentStudentId,
    //         template: template,
    //         message: content
    //     }),
    //     headers: {
    //         'Content-Type': 'application/json'
    //     }
    // })
    // .then(response => response.json())
    // .then(data => {
    //     hideLoading();
    //     document.getElementById('notificationModal').style.display = 'none';
    //     alert(`ส่งข้อความแจ้งเตือนสำเร็จ!`);
    // })
    // .catch(error => {
    //     console.error('Error sending notification:', error);
    //     hideLoading();
    //     alert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
    // });
    
    // Simular envío
    setTimeout(() => {
        hideLoading();
        
        // Cerrar el modal
        const notificationModal = document.getElementById('notificationModal');
        if (notificationModal) {
            notificationModal.style.display = 'none';
        }
        
        // Notificar éxito
        alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองนักเรียนรหัส ${currentStudentId} เรียบร้อยแล้ว`);
    }, 800);
}

/**
 * Solicita confirmación para notificar a todos los estudiantes en riesgo
 */
function confirmNotifyAllRiskStudents() {
    if (confirm('คุณต้องการส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดหรือไม่?')) {
        notifyAllRiskStudents();
    }
}

/**
 * Solicita confirmación para notificar a un grupo específico
 * @param {string} group - Tipo de grupo ('risk' o 'failed')
 */
function confirmNotifyGroup(group) {
    let message = '';
    if (group === 'risk') {
        message = 'คุณต้องการส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรม (70% - 80%) ทั้งหมดหรือไม่?';
    } else if (group === 'failed') {
        message = 'คุณต้องการส่งข้อความแจ้งเตือนฉุกเฉินไปยังผู้ปกครองของนักเรียนที่ตกกิจกรรม (น้อยกว่า 70%) ทั้งหมดหรือไม่?';
    }
    
    if (message && confirm(message)) {
        notifyStudentGroup(group);
    }
}

/**
 * Envía notificaciones a todos los estudiantes en riesgo
 */
function notifyAllRiskStudents() {
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch('api/reports.php?action=notify_all_risk_students', {
    //     method: 'POST'
    // })
    // .then(response => response.json())
    // .then(data => {
    //     hideLoading();
    //     alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดแล้ว (${data.sent_count} คน)`);
    // })
    // .catch(error => {
    //     console.error('Error sending notifications:', error);
    //     hideLoading();
    //     alert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
    // });
    
    // Simular envío
    setTimeout(() => {
        hideLoading();
        
        // Notificar éxito
        alert('ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดเรียบร้อยแล้ว');
    }, 1200);
}

/**
 * Envía notificaciones a un grupo específico de estudiantes
 * @param {string} group - Tipo de grupo ('risk' o 'failed')
 */
function notifyStudentGroup(group) {
    showLoading();
    
    // En una implementación real, aquí se haría una petición AJAX
    // fetch('api/reports.php?action=notify_student_group', {
    //     method: 'POST',
    //     body: JSON.stringify({
    //         group: group
    //     }),
    //     headers: {
    //         'Content-Type': 'application/json'
    //     }
    // })
    // .then(response => response.json())
    // .then(data => {
    //     hideLoading();
    //     alert(`ส่งข้อความแจ้งเตือนเรียบร้อยแล้ว (${data.sent_count} คน)`);
    // })
    // .catch(error => {
    //     console.error('Error sending group notifications:', error);
    //     hideLoading();
    //     alert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน');
    // });
    
    // Simular envío
    setTimeout(() => {
        hideLoading();
        
        let message = '';
        if (group === 'risk') {
            message = `ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมเรียบร้อยแล้ว`;
        } else if (group === 'failed') {
            message = `ส่งข้อความแจ้งเตือนฉุกเฉินไปยังผู้ปกครองของนักเรียนที่ตกกิจกรรมเรียบร้อยแล้ว`;
        }
        
        alert(message);
    }, 1200);
}

/**
 * Muestra el indicador de carga
 */
function showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
}

/**
 * Oculta el indicador de carga
 */
function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}