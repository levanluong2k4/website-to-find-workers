# Admin Customer Management Spec

## 1. Muc tieu module

Module nay giup admin theo doi toan bo vong doi khach hang:

- khach moi dang vao he thong
- khach dang co don can xu ly
- khach da dung dich vu nhieu lan
- khach co danh gia xau, khieu nai, huy don
- khach can cham soc de quay lai

Module can tra loi nhanh 4 cau hoi:

1. Khach nay la ai?
2. Khach nay da dat gi va dang o trang thai nao?
3. Khach nay co van de gi can admin can thiep?
4. Khach nay co gia tri cao hay dang co nguy co roi bo?

## 2. Uu tien build

Lam truoc 4 trang:

1. `/admin/customers`
2. `/admin/customers/{id}`
3. `/admin/customers/{id}/bookings`
4. `/admin/customer-feedback`

Lam sau:

5. `/admin/customer-segments`
6. `/admin/customer-care`

## 3. Menu admin de xuat

Them nhom `Khach hang` vao sidebar admin.

- `Tong quan khach hang`
  - co the gom vao trang `/admin/customers` bang thanh KPI tren cung
- `Danh sach khach hang`
  - route: `/admin/customers`
- `Chi tiet khach hang`
  - route: `/admin/customers/{id}`
- `Lich su don cua khach`
  - route: `/admin/customers/{id}/bookings`
- `Phan hoi va khieu nai`
  - route: `/admin/customer-feedback`
- `Phan nhom khach`
  - route: `/admin/customer-segments`
- `Cham soc khach`
  - route: `/admin/customer-care`

## 4. Flow dieu huong

### Flow 1: theo doi va tim khach

- vao `Danh sach khach hang`
- tim theo ten, SDT, email, ma khach
- loc theo khu vuc, so don, muc chi tieu, lan dat gan nhat
- click vao dong khach de mo `Chi tiet khach hang`

### Flow 2: khach goi len tong dai

- vao `Danh sach khach hang`
- tim bang SDT
- mo `Chi tiet khach hang`
- xem don gan nhat, lich su danh gia, ghi chu noi bo
- neu can, nhay sang `Lich su don cua khach`

### Flow 3: xu ly khieu nai

- vao `Phan hoi va khieu nai`
- loc case theo muc do uu tien va trang thai
- mo chi tiet case
- xem don lien quan, tho phu trach, danh gia cua khach
- cap nhat ket qua xu ly va ghi chu noi bo

## 5. Wireframe trang 1: Danh sach khach hang

### Muc tieu

Cho admin nhin nhanh toan bo tap khach va loc ra nhom can xu ly ngay.

### Cau truc man hinh

#### Header

- title: `Khach hang`
- subtitle: `Theo doi khach moi, khach dang co don, khach quay lai va khach can cham soc.`
- action:
  - `Xuat file`
  - `Tao tag`
  - `Lam moi`

#### KPI row

- `Tong khach hang`
- `Khach moi 7 ngay`
- `Khach dang co don mo`
- `Khach can cham soc`
- `Khach danh gia thap`

#### Thanh loc

- search: ten, SDT, email, ma khach
- filter:
  - trang thai: `dang hoat dong`, `co don mo`, `ngung tuong tac`, `can xu ly`
  - khu vuc
  - so don: `1`, `2-5`, `>5`
  - muc chi tieu: `thap`, `trung binh`, `cao`
  - lan dat gan nhat
  - tag khach hang

#### Bang du lieu

Cot de xuat:

- ma khach
- ten khach
- SDT
- khu vuc chinh
- so don
- don gan nhat
- tong chi tieu
- diem danh gia TB
- trang thai hien tai
- tag
- thao tac

#### Drawer / quick view

Khi click nhanh vao 1 dong:

- ten, avatar, SDT, email
- don gan nhat
- tong so don
- tong chi tieu
- tho phuc vu gan nhat
- diem danh gia
- ghi chu noi bo
- nut:
  - `Xem chi tiet`
  - `Xem lich su don`
  - `Them ghi chu`

### Trang thai mau

- xanh la: khach tot, quay lai deu
- vang: lau chua quay lai
- do: co khieu nai hoac danh gia thap
- xanh duong: dang co don dang xu ly

## 6. Wireframe trang 2: Chi tiet khach hang 360

### Muc tieu

Cho admin nhin duoc toan bo boi canh cua 1 khach tren cung mot man.

### Cau truc man hinh

#### Cum thong tin dau trang

- avatar
- ten khach
- ma khach
- SDT
- email
- dia chi thuong dung
- ngay tham gia he thong
- tag khach hang

#### KPI ca nhan

- tong so don
- tong chi tieu
- gia tri don trung binh
- lan dat gan nhat
- diem danh gia trung binh
- so lan huy / khieu nai

#### The `Tinh trang hien tai`

- co don nao dang mo khong
- don gan nhat dang o trang thai nao
- co no thanh toan / tranh chap / khieu nai khong
- tho nao dang phu trach

#### The `Dich vu va thiet bi hay dung`

- top 3 dich vu da dat
- top thiet bi hay sua
- hinh thuc hay dung: tai nha / tai cua hang

#### The `Timeline hoat dong`

Sap theo moi nhat truoc:

- tao tai khoan
- tao don
- xac nhan don
- dang lam
- hoan thanh
- thanh toan
- danh gia
- khiu nai / ghi chu admin

#### The `Ghi chu noi bo`

- ghi chu CSKH
- ghi chu ke toan
- ghi chu van hanh
- lich su cap nhat, ai cap nhat, thoi gian

#### Action nhanh

- `Xem lich su don`
- `Tao ghi chu`
- `Gan tag`
- `Mo feedback`

## 7. Wireframe trang 3: Lich su don cua khach

### Muc tieu

Cho admin tra cuu nhanh tat ca don cua mot khach.

### Cau truc man hinh

#### Header nho

- breadcrumb: `Khach hang / Nguyen Van A / Lich su don`
- thong tin tom tat: tong don, tong chi tieu, don dang mo

#### Bo loc

- khoang ngay
- trang thai don
- dich vu
- tho phu trach
- hinh thuc sua
- da thanh toan / chua thanh toan

#### Bang don

Cot de xuat:

- ma don
- ngay hen
- dich vu
- hinh thuc
- tho phu trach
- trang thai
- tong tien
- thanh toan
- danh gia
- thao tac

#### Action tren tung dong

- `Xem chi tiet don`
- `Mo vi tri`
- `Xem chi phi`
- `Xem feedback`

#### Panel tong hop ben phai hoac sticky row

- tong so don hoan thanh
- tong so don huy
- tong da chi
- thoi gian xu ly TB
- top tho phuc vu khach nay

## 8. Wireframe trang 4: Phan hoi va khieu nai

### Muc tieu

Giup admin quan ly chat luong dich vu tu goc nhin khach hang.

### Cau truc man hinh

#### Header

- title: `Phan hoi va khieu nai`
- subtitle: `Theo doi danh gia thap, khieu nai dang mo va cac case can xu ly som.`

#### KPI row

- feedback moi hom nay
- case dang mo
- case qua han xu ly
- danh gia 1-2 sao
- khach chua duoc phan hoi

#### Bo loc

- loai: `danh gia thap`, `khieu nai`, `goi lai`, `yeu cau ho tro`
- muc do uu tien: `cao`, `trung binh`, `thap`
- trang thai: `moi`, `dang xu ly`, `da dong`
- theo tho
- theo dich vu
- theo khu vuc

#### Bang / board case

Cot de xuat:

- ma case
- khach hang
- don lien quan
- tho lien quan
- loai van de
- muc do uu tien
- lan cap nhat cuoi
- deadline xu ly
- trang thai
- nguoi phu trach

#### Chi tiet case

- thong tin khach
- thong tin don
- noi dung phan anh
- anh / video / bang chung
- lich su trao doi
- huong xu ly da ap dung
- ket luan cuoi

#### Action nhanh

- `Nhan xu ly`
- `Lien he khach`
- `Lien he tho`
- `Danh dau da giai quyet`
- `Chuyen cap quan ly`

## 9. Du lieu can co tren backend

### Nguon du lieu khach hang

- bang user
- bang don_dat_lich
- bang danh_gia
- bang thong_bao / log tuong tac neu co
- bang ghi_chu_noi_bo neu se them moi
- bang tag_khach_hang neu se them moi

### Truong du lieu khach hang can tong hop

- id
- ma_khach
- name
- phone
- email
- avatar
- created_at
- khu_vuc_chinh
- tong_so_don
- tong_don_hoan_thanh
- tong_don_huy
- tong_chi_tieu
- gia_tri_trung_binh
- lan_dat_gan_nhat
- diem_danh_gia_tb
- so_feedback_xau
- so_case_dang_mo
- trang_thai_quan_he
- tags[]

### Truong du lieu tren case feedback

- case_id
- customer_id
- booking_id
- worker_id
- loai_case
- muc_do_uu_tien
- noi_dung
- trang_thai
- assigned_admin_id
- due_at
- resolved_at
- ket_qua_xu_ly

## 10. API goi y

### Danh sach khach hang

- `GET /api/admin/customers`
- params:
  - `search`
  - `status`
  - `area`
  - `order_count_range`
  - `spending_range`
  - `last_booking_range`
  - `tags`
  - `page`
  - `per_page`

### Chi tiet khach hang

- `GET /api/admin/customers/{id}`

### Lich su don cua khach

- `GET /api/admin/customers/{id}/bookings`

### Feedback / khieu nai

- `GET /api/admin/customer-feedback`
- `GET /api/admin/customer-feedback/{id}`
- `POST /api/admin/customer-feedback/{id}/assign`
- `POST /api/admin/customer-feedback/{id}/resolve`

### Ghi chu noi bo / tag

- `POST /api/admin/customers/{id}/notes`
- `POST /api/admin/customers/{id}/tags`

## 11. Components can tai su dung

- admin page header
- KPI card row
- filter toolbar
- data table
- side detail drawer
- status pill
- tag pill
- timeline list
- feedback case panel

Nen lam theo huong nay de UI dong bo voi `admin.dashboard`, `admin.bookings`, `admin.dispatch`.

## 12. Thu tu implement de xuat

### Phase 1

- route + menu `Khach hang`
- trang `Danh sach khach hang`
- API list customer co KPI tong hop co ban

### Phase 2

- trang `Chi tiet khach hang 360`
- API detail customer
- timeline va ghi chu noi bo

### Phase 3

- trang `Lich su don cua khach`
- lien ket sang chi tiet don

### Phase 4

- trang `Phan hoi va khieu nai`
- case management

## 13. Quyet dinh UX

- Khong tach rieng `Tong quan khach hang` thanh 1 trang doc lap o phase dau.
- Dat KPI ngay dau `Danh sach khach hang` de admin vao la thao tac duoc ngay.
- `Chi tiet khach hang` phai la man 360, khong chia qua nhieu tab o ban dau.
- `Lich su don` uu tien dang bang loc nhanh, khong uu tien card.
- `Phan hoi va khieu nai` uu tien case table + detail drawer, de van hanh nhanh.

## 14. Buoc tiep theo de build ngay

Neu bat dau code ngay, lam theo thu tu:

1. tao route web admin cho `customers`
2. tao view `resources/views/admin/customers.blade.php`
3. dung layout va style gan voi `admin.dashboard`
4. tao API `GET /api/admin/customers`
5. do du lieu that vao bang list truoc khi lam detail page
