// src/components/Comment.jsx
import React, { useEffect, useState, useMemo } from "react";
import { useComments } from "../../../contexts/CommentContext";
import { toast } from "react-toastify";

// Component hiển thị từng bình luận (bao gồm trả lời lồng nhau)
const CommentItem = ({
  cmt,
  replyTo,
  setReplyTo,
  onReplySubmit,
  replyContent,
  setReplyContent,
  loading,
  level = 0,
}) => (
  <div className={`mt-4 ${level > 0 ? "ml-8" : ""}`}>
    <div className="flex items-start space-x-3">
      {/* Avatar */}
      <div className="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold shadow">
        {cmt.user?.name?.charAt(0).toUpperCase() || "U"}
      </div>

      <div className="flex-1 bg-gray-50 rounded-xl p-3 shadow-sm">
        <div className="flex justify-between items-center">
          <p className="font-semibold text-gray-800">{cmt.user?.name}</p>
          <span className="text-xs text-gray-400">
            {new Date(cmt.created_at).toLocaleDateString("vi-VN")}
          </span>
        </div>
        <p className="text-gray-700 mt-1 text-sm leading-relaxed">
          {cmt.content}
        </p>

        <button
          onClick={() => setReplyTo(cmt.id)}
          className="text-xs text-red-500 hover:text-red-600 font-medium mt-2"
        >
          Trả lời
        </button>

        {replyTo === cmt.id && (
          <form onSubmit={(e) => onReplySubmit(e, cmt.id)} className="mt-3">
            <textarea
              value={replyContent[cmt.id] || ""}
              onChange={(e) =>
                setReplyContent((prev) => ({
                  ...prev,
                  [cmt.id]: e.target.value,
                }))
              }
              rows="2"
              className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-red-400 outline-none"
              placeholder="Viết trả lời..."
            />
            <div className="flex justify-end mt-2 space-x-2">
              <button
                type="button"
                onClick={() => setReplyTo(null)}
                className="px-4 py-1 text-sm bg-gray-200 text-gray-600 rounded-lg hover:bg-gray-300"
              >
                Hủy
              </button>
              <button
                type="submit"
                disabled={!replyContent[cmt.id]?.trim() || loading}
                className={`px-4 py-1 text-sm rounded-lg shadow-md transition ${
                  !replyContent[cmt.id]?.trim() || loading
                    ? "bg-gray-300 text-gray-500 cursor-not-allowed"
                    : "bg-red-500 text-white hover:bg-red-600"
                }`}
              >
                {loading ? "Đang gửi..." : "Gửi trả lời"}
              </button>
            </div>
          </form>
        )}

        {/* Danh sách trả lời con */}
        {cmt.children?.length > 0 && (
          <div className="mt-3 space-y-3">
            {cmt.children.map((rep) => (
              <CommentItem
                key={rep.id}
                cmt={rep}
                replyTo={replyTo}
                setReplyTo={setReplyTo}
                onReplySubmit={onReplySubmit}
                replyContent={replyContent}
                setReplyContent={setReplyContent}
                loading={loading}
                level={level + 1}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  </div>
);

const PAGE_SIZE = 3; // ⬅️ hiển thị 3 bình luận gốc đầu tiên

const Comment = ({ productId }) => {
  const { comments, loading, fetchComments, addComment } = useComments();
  const [rootContent, setRootContent] = useState("");
  const [replyContent, setReplyContent] = useState({});
  const [replyTo, setReplyTo] = useState(null);
  const [commentHistory, setCommentHistory] = useState([]);
  const [showAll, setShowAll] = useState(false);

  const user = JSON.parse(localStorage.getItem("user")) || null;

  useEffect(() => {
    fetchComments(productId);
    setShowAll(false); // reset khi đổi sản phẩm
  }, [productId]);

  // danh sách bình luận gốc sau khi cắt trang
  const visibleComments = useMemo(() => {
    if (!comments?.length) return [];
    return showAll ? comments : comments.slice(0, PAGE_SIZE);
  }, [comments, showAll]);

  // ✅ Giới hạn 3 bình luận trong 5 phút
  const canComment = () => {
    const now = Date.now();
    const fiveMinutes = 5 * 60 * 1000; // 5 phút
    const recent = commentHistory.filter((time) => now - time < fiveMinutes);

    if (recent.length >= 3) {
      toast.error(
        "Bạn đã bình luận quá nhiều lần, vui lòng thử lại sau ít phút!"
      );
      return false;
    }

    setCommentHistory([...recent, now]);
    return true;
  };

  const handleRootSubmit = async (e) => {
    e.preventDefault();
    if (!rootContent.trim()) {
      toast.error("Vui lòng nhập nội dung bình luận!");
      return;
    }
    if (!canComment()) return;

    if (await addComment(productId, rootContent)) {
      setRootContent("");
      // fetchComments(productId); // nếu addComment không tự cập nhật, mở dòng này
    }
  };

  const handleReplySubmit = async (e, parentId) => {
    e.preventDefault();
    const content = replyContent[parentId] || "";
    if (!content.trim()) {
      toast.error("Vui lòng nhập nội dung trả lời!");
      return;
    }
    if (!canComment()) return;

    if (await addComment(productId, content, parentId)) {
      setReplyContent((prev) => ({ ...prev, [parentId]: "" }));
      setReplyTo(null);
      // fetchComments(productId);
    }
  };

  return (
    <div className="bg-white p-6 rounded-xl shadow-lg">
      <h3 className="text-xl font-semibold mb-6 border-b pb-3 text-gray-800">
        Bình luận sản phẩm{" "}
        <span className="text-red-500">({comments.length})</span>
      </h3>

      {/* Form bình luận */}
      {user ? (
        <form onSubmit={handleRootSubmit} className="mb-6">
          <div className="flex items-start space-x-3">
            <div className="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg shadow">
              {user.name.charAt(0).toUpperCase()}
            </div>
            <div className="flex-1">
              <textarea
                value={rootContent}
                onChange={(e) => setRootContent(e.target.value)}
                rows="3"
                className="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-red-400 outline-none resize-none text-sm"
                placeholder="Hãy chia sẻ cảm nhận của bạn..."
              />
              <div className="flex justify-end mt-2">
                <button
                  type="submit"
                  disabled={!rootContent.trim() || loading}
                  className={`px-6 py-2 text-sm font-medium rounded-lg shadow-md transition ${
                    !rootContent.trim() || loading
                      ? "bg-gray-300 text-gray-500 cursor-not-allowed"
                      : "bg-red-500 text-white hover:bg-red-600"
                  }`}
                >
                  {loading ? "Đang gửi..." : "Gửi bình luận"}
                </button>
              </div>
            </div>
          </div>
        </form>
      ) : (
        <p className="text-gray-500 mb-6 text-center">
          Bạn cần{" "}
          <a href="/login" className="text-red-500 underline font-medium">
            đăng nhập
          </a>{" "}
          để bình luận.
        </p>
      )}

      {/* Danh sách bình luận */}
      {loading ? (
        <p className="text-gray-500 text-center">Đang tải bình luận...</p>
      ) : comments.length ? (
        <>
          <div className="space-y-4">
            {visibleComments.map((cmt) => (
              <CommentItem
                key={cmt.id}
                cmt={cmt}
                replyTo={replyTo}
                setReplyTo={setReplyTo}
                onReplySubmit={handleReplySubmit}
                replyContent={replyContent}
                setReplyContent={setReplyContent}
                loading={loading}
              />
            ))}
          </div>

          {/* Nút xem thêm / thu gọn ở cuối, căn giữa */}
          {comments.length > PAGE_SIZE && (
            <div className="mt-6 flex justify-center">
              {!showAll ? (
                <button
                  onClick={() => setShowAll(true)}
                  className="px-6 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium shadow-sm transition"
                >
                  Xem tất cả bình luận &nbsp; →
                </button>
              ) : (
                <button
                  onClick={() => setShowAll(false)}
                  className="px-6 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium shadow-sm transition"
                >
                  Thu gọn
                </button>
              )}
            </div>
          )}
        </>
      ) : (
        <p className="text-gray-500 text-center">Chưa có bình luận nào.</p>
      )}
    </div>
  );
};

export default Comment;
