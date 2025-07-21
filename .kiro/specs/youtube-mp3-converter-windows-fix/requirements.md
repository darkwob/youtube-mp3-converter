# Requirements Document

## Introduction

Bu özellik, mevcut YouTube MP3 dönüştürücü projesindeki Windows platform uyumluluğu sorunlarını ve temp dosya oluşturma problemlerini çözmek için geliştirilecektir. Proje şu anda eksik YouTubeConverter sınıfı nedeniyle çalışmamakta ve Windows işletim sisteminde yt-dlp ve ffmpeg binary'lerinin doğru şekilde kullanılamaması gibi sorunlar yaşanmaktadır.

## Requirements

### Requirement 1

**User Story:** Bir geliştirici olarak, YouTube videolarını Windows işletim sisteminde sorunsuz şekilde MP3 formatına dönüştürebilmek istiyorum, böylece platform bağımsız bir çözüm elde edebilirim.

#### Acceptance Criteria

1. WHEN sistemde `yt-dlp` binary'si çalıştırılmak istendiğinde  
   THEN sistem, işletim sistemi fark etmeksizin uzantı kontrolü yapmadan yalnızca adıyla eşleştirerek binary'yi bulup çalıştırabilmelidir.

2. WHEN sistemde `ffmpeg` binary'si çalıştırılmak istendiğinde  
   THEN sistem, işletim sistemi fark etmeksizin uzantı kontrolü yapmadan yalnızca adıyla eşleştirerek binary'yi bulup çalıştırabilmelidir.

3. WHEN `bin/` klasöründe gerekli binary dosyaları bulunamazsa  
   THEN sistem, çalışmayı durdurmalı ve kullanıcıya bulunduğu platforma uygun açık, yönlendirici kurulum talimatlarını göstermelidir.

4. WHEN kullanıcı özel bir binary yolu tanımladığında  
   THEN sistem, bu yolu doğrudan kullanabilmeli; platforma uygun yol formatını doğru şekilde yorumlayarak, uzantı farkı gözetmeksizin dosyanın varlığını ve çalıştırılabilirliğini kontrol edebilmelidir.


### Requirement 2

**User Story:** Bir geliştirici olarak, temp klasörlerinin otomatik olarak oluşturulmasını ve yönetilmesini istiyorum, böylece manuel klasör oluşturma işlemleri yapmak zorunda kalmam.

#### Acceptance Criteria

1. WHEN YouTubeConverter başlatıldığında THEN gerekli temp klasörleri otomatik olarak oluşturulmalı
2. WHEN temp klasörü mevcut değilse THEN sistem uygun izinlerle klasörü oluşturmalı
3. WHEN temp klasörü oluşturulamadığında THEN sistem anlamlı hata mesajı vermeli
4. WHEN işlem tamamlandığında THEN temp dosyalar temizlenebilmeli

### Requirement 3

**User Story:** Bir geliştirici olarak, eksik olan YouTubeConverter sınıfının tam işlevsel olarak implement edilmesini istiyorum, böylece video dönüştürme işlemlerini gerçekleştirebilirim.

#### Acceptance Criteria

1. WHEN YouTubeConverter sınıfı çağrıldığında THEN video bilgilerini alabilmeli
2. WHEN video URL'si verildiğinde THEN video indirme işlemini başlatabilmeli
3. WHEN video indirildiğinde THEN MP3 formatına dönüştürme işlemini gerçekleştirebilmeli
4. WHEN işlem sırasında hata oluştuğunda THEN uygun exception fırlatmalı
5. WHEN progress tracking istendiğinde THEN işlem ilerlemesini takip edebilmeli

### Requirement 4

**User Story:** Bir geliştirici olarak, Windows'ta path separator ve dosya uzantısı sorunlarının çözülmesini istiyorum, böylece cross-platform uyumluluk sağlanabilsin.

#### Acceptance Criteria

1. WHEN Windows'ta dosya path'i oluşturulduğunda THEN backslash separator kullanılmalı
2. WHEN executable dosya arandığında THEN Windows'ta .exe uzantısı otomatik eklenmeli
3. WHEN relative path verildiğinde THEN absolute path'e doğru şekilde çevrilmeli
4. WHEN path normalization yapıldığında THEN platform-specific format kullanılmalı

### Requirement 5

**User Story:** Bir geliştirici olarak, runtime hatalarının yakalanıp anlamlı hata mesajları verilmesini istiyorum, böylece sorun giderme işlemlerini kolayca yapabileyim.

#### Acceptance Criteria

1. WHEN binary bulunamadığında THEN kurulum talimatları içeren hata mesajı verilmeli
2. WHEN dosya izin hatası oluştuğunda THEN chmod talimatları içeren hata mesajı verilmeli
3. WHEN network hatası oluştuğunda THEN bağlantı sorununu açıklayan hata mesajı verilmeli
4. WHEN timeout oluştuğunda THEN işlem durumu hakkında bilgi verilmeli
5. WHEN genel hata oluştuğunda THEN debug bilgileri log'lanmalı

### Requirement 6

**User Story:** Bir geliştirici olarak, demo uygulamasının tam çalışır durumda olmasını istiyorum, böylece projeyi test edip kullanabileyim.

#### Acceptance Criteria

1. WHEN demo/process.php çalıştırıldığında THEN YouTubeConverter sınıfını bulabilmeli
2. WHEN video URL'si gönderildiğinde THEN işlem başarıyla başlatılabilmeli
3. WHEN progress kontrolü yapıldığında THEN güncel durum bilgisi alınabilmeli
4. WHEN işlem tamamlandığında THEN indirilen dosya erişilebilir olmalı