import { StatusBar } from 'expo-status-bar';
import { useState } from 'react';
import {
  Pressable,
  SafeAreaView,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from 'react-native';

const colors = {
  background: '#0b1326',
  surface: '#171f33',
  surfaceLow: '#131b2e',
  surfaceHigh: '#222a3d',
  text: '#dae2fd',
  muted: '#9da8c0',
  primary: '#ffb2ba',
  primaryContainer: '#ff4f74',
  onPrimaryContainer: '#5a001c',
  tertiary: '#7bd0ff',
  border: '#354057',
  notice: '#10293a',
};

const maxMessageLength = 300;

export default function App() {
  const [message, setMessage] = useState('');
  const [previewSubmitted, setPreviewSubmitted] = useState(false);
  const { width } = useWindowDimensions();
  const compact = width < 430;

  function submitPreview() {
    setPreviewSubmitted(true);
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar style="light" />
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <View style={styles.brandMark}>
            <Text style={styles.brandMarkText}>M</Text>
          </View>
          <Text style={styles.brandText}>MOVIE <Text style={styles.brandAccent}>BUDDY</Text></Text>
          <View style={styles.headerSpacer} />
          <View style={styles.sosPill}>
            <Text style={styles.sosMark}>!</Text>
            <Text style={styles.sosText}>SOS</Text>
          </View>
        </View>

        <View style={styles.content}>
          <Text style={styles.breadcrumb}>Khám phá  /  Chi tiết kế hoạch</Text>

          <View style={styles.demoNotice}>
            <View style={styles.noticeDot} />
            <Text style={styles.demoNoticeText}>
              Bản xem trước giao diện — dữ liệu minh họa, chưa kết nối máy chủ.
            </Text>
          </View>

          <View style={styles.movieCard}>
            <View style={[styles.movieRow, compact && styles.movieRowCompact]}>
              <View style={[styles.movieArt, compact && styles.movieArtCompact]}>
                <Text style={styles.artLabel}>MOVIE NIGHT</Text>
                <Text style={[styles.artTitle, compact && styles.artTitleCompact]}>
                  DUNE{'\n'}PART TWO
                </Text>
              </View>
              <View style={styles.movieInfo}>
                <Text style={styles.eyebrow}>KẾ HOẠCH XEM PHIM</Text>
                <Text style={[styles.movieTitle, compact && styles.movieTitleCompact]}>
                  Dune: Hành Tinh Cát 2
                </Text>
                <Text style={styles.subtitle}>Khoa học viễn tưởng · 166 phút</Text>
                <View style={styles.tags}>
                  <View style={styles.tagBlue}><Text style={styles.tagBlueText}>IMAX</Text></View>
                  <View style={styles.tag}><Text style={styles.tagText}>Không tiết lộ nội dung</Text></View>
                </View>
              </View>
            </View>
            <View style={styles.detailStrip}>
              <View style={styles.detailItem}>
                <Text style={styles.detailIcon}>◷</Text>
                <View>
                  <Text style={styles.detailLabel}>SUẤT CHIẾU</Text>
                  <Text style={styles.detailValue}>20:00 · Hôm nay</Text>
                </View>
              </View>
              <View style={styles.detailItem}>
                <Text style={[styles.detailIcon, styles.cyan]}>⌖</Text>
                <View>
                  <Text style={styles.detailLabel}>RẠP CHIẾU</Text>
                  <Text style={styles.detailValue}>CGV Vạn Hạnh Mall</Text>
                  <Text style={styles.detailHint}>Quận 10, TP. Hồ Chí Minh</Text>
                </View>
              </View>
            </View>
          </View>

          <View style={styles.hostCard}>
            <View style={styles.hostHeader}>
              <View style={styles.avatar}><Text style={styles.avatarText}>NM</Text></View>
              <View style={styles.hostIdentity}>
                <Text style={styles.hostName}>Ngọc Mai <Text style={styles.verified}>✓</Text></Text>
                <Text style={styles.hostMeta}>Photo Verified · Tuổi tự khai</Text>
              </View>
              <View style={styles.rating}>
                <Text style={styles.ratingText}>★ 4.9</Text>
                <Text style={styles.ratingSubtext}>12 đánh giá</Text>
              </View>
            </View>
            <View style={styles.divider} />
            <Text style={styles.sectionLabel}>GU ĐIỆN ẢNH YÊU THÍCH</Text>
            <View style={styles.tags}>
              <View style={styles.tag}><Text style={styles.tagText}>Khoa học viễn tưởng</Text></View>
              <View style={styles.tag}><Text style={styles.tagText}>Phiêu lưu</Text></View>
              <View style={styles.tag}><Text style={styles.tagText}>Không tiết lộ</Text></View>
            </View>
            <Text style={styles.quote}>
              “Mình muốn tìm một người bạn cùng xem phim và trò chuyện vui vẻ sau suất chiếu. Rất mong được gặp bạn!”
            </Text>
          </View>

          <View style={styles.requestCard}>
            <Text style={styles.requestKicker}>✦  LỜI XIN THAM GIA</Text>
            <View style={styles.requestTitleRow}>
              <Text style={styles.requestTitle}>Gửi lời xin tham gia</Text>
              <View style={styles.openBadge}>
                <View style={styles.onlineDot} />
                <Text style={styles.openBadgeText}>Đang mở</Text>
              </View>
            </View>

            <View style={styles.hostSummary}>
              <View style={[styles.avatar, styles.avatarSmall]}><Text style={styles.avatarSmallText}>NM</Text></View>
              <View style={styles.summaryIdentity}>
                <Text style={styles.summaryName}>Ngọc Mai</Text>
                <Text style={styles.summaryRole}>Chủ kế hoạch · <Text style={styles.verified}>✓</Text></Text>
              </View>
              <Text style={styles.summaryRating}>★ 4.9</Text>
            </View>

            <Text style={styles.inputLabel}>
              Lời nhắn <Text style={styles.optional}>(không bắt buộc)</Text>
            </Text>
            <View style={styles.inputWrap}>
              <TextInput
                accessibilityLabel="Lời nhắn gửi chủ kế hoạch"
                maxLength={maxMessageLength}
                multiline
                onChangeText={(value) => {
                  setMessage(value);
                  setPreviewSubmitted(false);
                }}
                placeholder="Chia sẻ ngắn gọn về bạn hoặc điều bạn thích ở bộ phim này..."
                placeholderTextColor="#777f98"
                style={styles.textInput}
                textAlignVertical="top"
                value={message}
              />
              <Text style={styles.characterCount}>{message.length}/{maxMessageLength}</Text>
            </View>
            <Text style={styles.hint}>
              Tối đa 300 ký tự. Không chia sẻ thông tin cá nhân nhạy cảm trong lời nhắn.
            </Text>

            <View style={styles.requestCount}>
              <Text style={styles.countIcon}>◎</Text>
              <Text style={styles.countText}><Text style={styles.countNumber}>3</Text> lời xin tham gia đang chờ</Text>
            </View>

            {previewSubmitted && (
              <View accessibilityLiveRegion="polite" style={styles.feedback}>
                <Text style={styles.feedbackText}>
                  Đây chỉ là thao tác xem thử. Chưa có yêu cầu nào được gửi lên máy chủ.
                </Text>
              </View>
            )}

            <Pressable
              accessibilityRole="button"
              onPress={submitPreview}
              style={({ pressed }) => [styles.submitButton, pressed && styles.submitPressed]}
            >
              <Text style={styles.submitButtonText}>
                ➤  {previewSubmitted ? 'Thử lại thao tác xem thử' : 'Gửi lời xin tham gia'}
              </Text>
            </Pressable>

            <Text style={styles.privacyNote}>
              <Text style={styles.lockIcon}>▣  </Text>
              Chủ kế hoạch sẽ xem lời nhắn trước khi quyết định.
            </Text>
          </View>

          <View style={styles.safetyNote}>
            <View style={styles.infoIcon}><Text style={styles.infoIconText}>i</Text></View>
            <Text style={styles.safetyText}>
              <Text style={styles.safetyTitle}>Lưu ý an toàn</Text>
              {'\n'}Chat chỉ mở sau khi chủ kế hoạch chọn bạn. Hãy gặp nhau tại khu vực công cộng của rạp.
            </Text>
          </View>

          <Text style={styles.footer}>MOVIE BUDDY  ·  Kết nối bạn xem phim văn minh tại rạp</Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.background,
  },
  scrollContent: {
    flexGrow: 1,
  },
  header: {
    minHeight: 62,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 18,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
    backgroundColor: colors.background,
  },
  brandMark: {
    width: 31,
    height: 31,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 9,
    backgroundColor: colors.primaryContainer,
  },
  brandMarkText: {
    color: colors.onPrimaryContainer,
    fontSize: 18,
    fontWeight: '800',
  },
  brandText: {
    marginLeft: 9,
    color: colors.text,
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 0.6,
  },
  brandAccent: {
    color: colors.primary,
  },
  headerSpacer: {
    flex: 1,
  },
  sosPill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#74343c',
    backgroundColor: '#321825',
  },
  sosMark: {
    width: 16,
    height: 16,
    marginRight: 5,
    overflow: 'hidden',
    borderRadius: 8,
    color: '#690005',
    backgroundColor: '#ffb4ab',
    textAlign: 'center',
    fontSize: 12,
    fontWeight: '800',
  },
  sosText: {
    color: '#ffd5d1',
    fontSize: 11,
    fontWeight: '700',
  },
  content: {
    width: '100%',
    maxWidth: 680,
    alignSelf: 'center',
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 28,
  },
  breadcrumb: {
    marginBottom: 14,
    color: colors.muted,
    fontSize: 11,
  },
  demoNotice: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 15,
    padding: 11,
    borderWidth: 1,
    borderColor: '#23465c',
    borderRadius: 10,
    backgroundColor: colors.notice,
  },
  noticeDot: {
    width: 7,
    height: 7,
    marginTop: 4,
    marginRight: 8,
    borderRadius: 4,
    backgroundColor: colors.tertiary,
  },
  demoNoticeText: {
    flex: 1,
    color: '#b6dff4',
    fontSize: 11,
    lineHeight: 16,
  },
  movieCard: {
    overflow: 'hidden',
    marginBottom: 14,
    borderRadius: 15,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  movieRow: {
    minHeight: 172,
    flexDirection: 'row',
  },
  movieRowCompact: {
    minHeight: 148,
  },
  movieArt: {
    width: 122,
    justifyContent: 'flex-end',
    padding: 12,
    backgroundColor: '#20204b',
  },
  movieArtCompact: {
    width: 96,
  },
  artLabel: {
    marginBottom: 5,
    color: colors.tertiary,
    fontSize: 8,
    fontWeight: '700',
    letterSpacing: 1,
  },
  artTitle: {
    color: '#f0e5e6',
    fontSize: 17,
    fontWeight: '800',
    lineHeight: 19,
  },
  artTitleCompact: {
    fontSize: 14,
    lineHeight: 16,
  },
  movieInfo: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 14,
    paddingVertical: 13,
  },
  eyebrow: {
    marginBottom: 7,
    color: colors.tertiary,
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.7,
  },
  movieTitle: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '700',
    lineHeight: 25,
  },
  movieTitleCompact: {
    fontSize: 17,
    lineHeight: 21,
  },
  subtitle: {
    marginTop: 5,
    marginBottom: 10,
    color: colors.muted,
    fontSize: 10,
  },
  tags: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
  },
  tag: {
    marginRight: 6,
    marginBottom: 5,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderWidth: 1,
    borderColor: '#39435e',
    borderRadius: 20,
    backgroundColor: colors.surfaceHigh,
  },
  tagText: {
    color: '#c7c9e8',
    fontSize: 9,
    fontWeight: '600',
  },
  tagBlue: {
    marginRight: 6,
    marginBottom: 5,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 20,
    backgroundColor: '#12394d',
  },
  tagBlueText: {
    color: colors.tertiary,
    fontSize: 9,
    fontWeight: '700',
  },
  detailStrip: {
    paddingHorizontal: 13,
    paddingVertical: 13,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: colors.border,
    backgroundColor: '#0c162a',
  },
  detailItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginVertical: 4,
  },
  detailIcon: {
    width: 25,
    color: colors.primary,
    fontSize: 19,
  },
  cyan: {
    color: colors.tertiary,
  },
  detailLabel: {
    marginBottom: 3,
    color: colors.muted,
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.6,
  },
  detailValue: {
    color: colors.text,
    fontSize: 11,
    fontWeight: '600',
  },
  detailHint: {
    marginTop: 2,
    color: colors.muted,
    fontSize: 10,
  },
  hostCard: {
    marginBottom: 14,
    padding: 15,
    borderRadius: 15,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  hostHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  avatar: {
    width: 44,
    height: 44,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: '#ba7890',
    backgroundColor: '#49324f',
  },
  avatarText: {
    color: '#ffe3e5',
    fontSize: 13,
    fontWeight: '700',
  },
  hostIdentity: {
    flex: 1,
  },
  hostName: {
    color: colors.text,
    fontSize: 14,
    fontWeight: '700',
  },
  verified: {
    color: colors.tertiary,
    fontWeight: '800',
  },
  hostMeta: {
    marginTop: 4,
    color: colors.tertiary,
    fontSize: 10,
  },
  rating: {
    alignItems: 'flex-end',
    marginLeft: 4,
  },
  ratingText: {
    color: '#ffd28b',
    fontSize: 12,
    fontWeight: '700',
  },
  ratingSubtext: {
    marginTop: 3,
    color: colors.muted,
    fontSize: 9,
  },
  divider: {
    height: StyleSheet.hairlineWidth,
    marginVertical: 13,
    backgroundColor: colors.border,
  },
  sectionLabel: {
    marginBottom: 8,
    color: colors.muted,
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.7,
  },
  quote: {
    marginTop: 8,
    padding: 11,
    borderLeftWidth: 2,
    borderLeftColor: colors.primaryContainer,
    borderTopRightRadius: 8,
    borderBottomRightRadius: 8,
    overflow: 'hidden',
    color: '#c9cde0',
    backgroundColor: '#0c162a',
    fontSize: 11,
    lineHeight: 17,
  },
  requestCard: {
    marginBottom: 14,
    padding: 17,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  requestKicker: {
    color: colors.primary,
    fontSize: 9,
    fontWeight: '700',
    letterSpacing: 0.8,
  },
  requestTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 10,
    marginBottom: 15,
  },
  requestTitle: {
    flex: 1,
    marginRight: 7,
    color: colors.text,
    fontSize: 20,
    fontWeight: '700',
  },
  openBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 6,
    borderRadius: 15,
    backgroundColor: '#12394d',
  },
  onlineDot: {
    width: 6,
    height: 6,
    marginRight: 5,
    borderRadius: 3,
    backgroundColor: colors.tertiary,
  },
  openBadgeText: {
    color: colors.tertiary,
    fontSize: 9,
    fontWeight: '600',
  },
  hostSummary: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 18,
    padding: 10,
    borderRadius: 11,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: '#0c162a',
  },
  avatarSmall: {
    width: 35,
    height: 35,
    marginRight: 9,
  },
  avatarSmallText: {
    color: '#ffe3e5',
    fontSize: 10,
    fontWeight: '700',
  },
  summaryIdentity: {
    flex: 1,
  },
  summaryName: {
    color: colors.text,
    fontSize: 11,
    fontWeight: '700',
  },
  summaryRole: {
    marginTop: 3,
    color: colors.muted,
    fontSize: 9,
  },
  summaryRating: {
    color: '#ffd28b',
    fontSize: 11,
    fontWeight: '700',
  },
  inputLabel: {
    marginBottom: 8,
    color: colors.text,
    fontSize: 11,
    fontWeight: '600',
  },
  optional: {
    color: colors.muted,
    fontWeight: '400',
  },
  inputWrap: {
    minHeight: 132,
    padding: 11,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#47516a',
    backgroundColor: '#0a1224',
  },
  textInput: {
    minHeight: 98,
    padding: 0,
    color: colors.text,
    fontSize: 12,
    lineHeight: 18,
  },
  characterCount: {
    alignSelf: 'flex-end',
    marginTop: 4,
    color: colors.muted,
    fontSize: 9,
  },
  hint: {
    marginTop: 7,
    marginBottom: 13,
    color: colors.muted,
    fontSize: 9,
    lineHeight: 14,
  },
  requestCount: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 13,
    padding: 10,
    borderRadius: 9,
    backgroundColor: colors.surfaceHigh,
  },
  countIcon: {
    marginRight: 7,
    color: colors.primary,
    fontSize: 15,
  },
  countText: {
    color: '#c6cbe2',
    fontSize: 10,
  },
  countNumber: {
    color: colors.primary,
    fontWeight: '700',
  },
  feedback: {
    marginBottom: 11,
    padding: 10,
    borderWidth: 1,
    borderColor: '#23465c',
    borderRadius: 9,
    backgroundColor: colors.notice,
  },
  feedbackText: {
    color: '#b6dff4',
    fontSize: 10,
    lineHeight: 15,
  },
  submitButton: {
    minHeight: 50,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 12,
    borderRadius: 11,
    backgroundColor: colors.primaryContainer,
  },
  submitPressed: {
    opacity: 0.82,
  },
  submitButtonText: {
    color: colors.onPrimaryContainer,
    fontSize: 12,
    fontWeight: '800',
  },
  privacyNote: {
    marginTop: 13,
    color: colors.muted,
    fontSize: 9,
    textAlign: 'center',
  },
  lockIcon: {
    color: colors.tertiary,
  },
  safetyNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    padding: 13,
    borderWidth: 1,
    borderColor: '#23465c',
    borderRadius: 12,
    backgroundColor: '#10202f',
  },
  infoIcon: {
    width: 21,
    height: 21,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 9,
    borderRadius: 11,
    backgroundColor: colors.tertiary,
  },
  infoIconText: {
    color: '#002d40',
    fontSize: 12,
    fontWeight: '800',
  },
  safetyText: {
    flex: 1,
    color: '#b9c9d9',
    fontSize: 10,
    lineHeight: 15,
  },
  safetyTitle: {
    color: colors.tertiary,
    fontWeight: '700',
  },
  footer: {
    marginTop: 20,
    color: colors.muted,
    fontSize: 9,
    textAlign: 'center',
  },
});
