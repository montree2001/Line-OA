/* 
 * parent-announcements.css - ไฟล์ CSS สำหรับหน้าประกาศและข่าวสารของผู้ปกครอง SADD-Prasat
 */

/* สไตล์ส่วนการค้นหาและกรอง */
.search-filter-section {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.search-form {
    margin-bottom: 15px;
}

.search-input {
    display: flex;
    align-items: center;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 0 15px;
    overflow: hidden;
    position: relative;
}

.search-input input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px;
    font-size: 15px;
    outline: none;
    color: var(--text-dark);
}

.search-input input::placeholder {
    color: var(--text-muted);
}

.search-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s ease;
}

.search-button:hover {
    background-color: var(--primary-color-dark);
}

.clear-search {
    font-size: 18px;
    color: var(--text-muted);
    cursor: pointer;
    margin-right: 8px;
    transition: color 0.2s ease;
    display: none;
}

.clear-search:hover {
    color: var(--text-dark);
}

.filter-buttons {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 5px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: var(--border-color) transparent;
}

.filter-buttons::-webkit-scrollbar {
    height: 4px;
}

.filter-buttons::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.filter-buttons::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 2px;
}

.filter-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-light);
    background-color: var(--bg-light);
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.filter-button:hover, 
.filter-button:active {
    background-color: #e8e8e8;
}

.filter-button.active {
    background-color: var(--primary-color);
    color: white;
}

.filter-button.important {
    color: var(--danger-color);
}

.filter-button.important.active {
    background-color: var(--danger-color);
    color: white;
}

.filter-button.event {
    color: var(--secondary-color);
}

.filter-button.event.active {
    background-color: var(--secondary-color);
    color: white;
}

.filter-button.exam {
    color: var(--warning-color);
}

.filter-button.exam.active {
    background-color: var(--warning-color);
    color: white;
}

/* ข้อความผลการค้นหา */
.search-result-message {
    display: flex;
    align-items: center;
    background-color: var(--secondary-color-light);
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    color: var(--secondary-color);
}

.search-result-message .material-icons {
    margin-right: 8px;
    flex-shrink: 0;
}

/* รายการประกาศ */
.announcements-list {
    margin-bottom: 30px;
}

.announcement-item {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.announcement-item::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background-color: var(--primary-color);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.announcement-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.announcement-item:hover::after {
    opacity: 1;
}

.announcement-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.announcement-category {
    font-size: 12px;
    font-weight: 600;
    color: white;
    background-color: var(--warning-color);
    padding: 3px 8px;
    border-radius: 12px;
    margin-right: 10px;
    flex-shrink: 0;
}

.announcement-category.event {
    background-color: var(--secondary-color);
}

.announcement-category.exam {
    background-color: var(--danger-color);
}

.announcement-category.important {
    background-color: var(--primary-color-dark);
}

.announcement-date {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: var(--text-muted);
    margin-left: auto;
}

.announcement-date .material-icons {
    font-size: 14px;
    margin-right: 4px;
}

.announcement-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--text-dark);
    transition: color 0.3s ease;
}

.announcement-title a {
    color: inherit;
    text-decoration: none;
}

.announcement-item:hover .announcement-title {
    color: var(--primary-color);
}

.announcement-excerpt {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 15px;
    line-height: 1.5;
}

.announcement-actions {
    display: flex;
    justify-content: flex-end;
}

.read-more-button {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--primary-color);
    background-color: var(--primary-color-light);
    text-decoration: none;
    transition: background-color 0.3s ease;
    position: relative;
    z-index: 2; /* ให้อยู่เหนือพื้นที่ที่คลิกได้ของ announcement-item */
}

.read-more-button:hover {
    background-color: #e8dbef;
}

.read-more-button .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

/* แบ่งหน้า */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 30px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 5px;
}

.pagination-button, .pagination-page {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    background-color: white;
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    min-width: 36px;
    justify-content: center;
}

.pagination-button {
    color: var(--primary-color);
}

.pagination-page.active {
    background-color: var(--primary-color);
    color: white;
}

.pagination-button:hover, 
.pagination-page:hover {
    background-color: var(--bg-light);
}

.pagination-button.prev .material-icons,
.pagination-button.next .material-icons {
    font-size: 18px;
}

.pagination-pages {
    display: flex;
    align-items: center;
    overflow-x: auto;
    max-width: 60%;
    scrollbar-width: none;
}

.pagination-pages::-webkit-scrollbar {
    display: none;
}

.pagination-ellipsis {
    margin: 0 5px;
    color: var(--text-light);
}

/* ไม่พบประกาศ */
.no-announcements {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #e0e0e0;
}

.no-data-message {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 15px;
}

.no-data-action {
    margin-top: 20px;
}

.reset-search-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.reset-search-button:hover {
    background-color: var(--primary-color-dark);
}

.reset-search-button .material-icons {
    margin-right: 5px;
}

/* รายละเอียดประกาศ */
.back-section {
    margin-bottom: 20px;
}

.back-button {
    display: inline-flex;
    align-items: center;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
    font-size: 15px;
}

.back-button:hover {
    color: var(--primary-color-dark);
}

.back-button .material-icons {
    margin-right: 5px;
}

.announcement-detail {
    background-color: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: var(--card-shadow);
    margin-bottom: 30px;
}

.announcement-detail .announcement-header {
    margin-bottom: 25px;
    flex-direction: column;
    align-items: flex-start;
}

.announcement-detail .announcement-meta {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;
    justify-content: space-between;
}

.announcement-detail .announcement-title {
    font-size: 24px;
    margin-bottom: 15px;
    line-height: 1.3;
    color: var(--text-dark);
}

.announcement-author {
    display: flex;
    align-items: center;
    font-size: 14px;
    color: var(--text-light);
    margin-top: 5px;
}

.announcement-author .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.announcement-content {
    font-size: 16px;
    line-height: 1.6;
    color: var(--text-dark);
}

/* เอฟเฟกต์การโหลด */
.loading-effect {
    position: relative;
    overflow: hidden;
}

.loading-effect::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
    animation: shimmer 1.5s infinite;
    transform: translateX(-100%);
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .search-filter-section {
        padding: 12px;
    }
    
    .filter-buttons {
        flex-wrap: nowrap;
        gap: 8px;
    }
    
    .filter-button {
        padding: 6px 10px;
        font-size: 12px;
    }
    
    .announcement-item {
        padding: 15px;
    }
    
    .announcement-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .announcement-date {
        margin-left: 0;
    }
    
    .announcement-category {
        margin-right: 0;
    }
    
    .announcement-title {
        font-size: 16px;
    }
    
    .announcement-detail .announcement-title {
        font-size: 20px;
    }
    
    .announcement-detail {
        padding: 15px;
    }
    
    .pagination {
        gap: 5px;
    }
    
    .pagination-pages {
        max-width: 50%;
    }
}

@media (max-width: 480px) {
    .search-input {
        flex-direction: row;
        padding: 5px 10px;
    }
    
    .search-input input {
        padding: 10px 5px;
        font-size: 14px;
    }
    
    .search-button {
        padding: 8px;
    }
    
    .filter-buttons {
        overflow-x: auto;
        padding-bottom: 10px;
    }
    
    .announcement-content {
        font-size: 14px;
    }
    
    .pagination-button, .pagination-page {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 32px;
    }
    
    .pagination-button.prev, .pagination-button.next {
        padding: 6px 8px;
    }
    
    .announcement-detail .announcement-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .reset-search-button {
        padding: 8px 15px;
        font-size: 13px;
    }
    
    .no-data-message {
        font-size: 14px;
    }
}

/* สไตล์เพิ่มเติมสำหรับการแสดงผลบนอุปกรณ์มือถือ */
@media (hover: none) {
    /* ปรับเมื่อไม่มีการ hover (อุปกรณ์สัมผัส) */
    .announcement-item::after {
        display: none;
    }
    
    .announcement-item:active {
        background-color: var(--bg-light);
    }
    
    .read-more-button:active {
        background-color: #e8dbef;
    }
}