import { useEffect, useState } from "react";

import { getDashboardOverview } from "./api/dashboardApi";
import OverviewPage from "./pages/OverviewPage";
import EntityDetailsPage from "./pages/EntityDetailsPage";
import MetricDetailsPage from "./pages/MetricDetailsPage";
import UserCoursesPage from "./pages/UserCoursesPage";
import UniversityStaffPage from "./pages/UniversityStaffPage";
import "./index.css";

export default function App() {
  const [entities, setEntities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState("");

  const [selectedEntity, setSelectedEntity] = useState(null);
  const [selectedMetric, setSelectedMetric] = useState(null);
  const [selectedUserCourses, setSelectedUserCourses] = useState(null);
  const [selectedStaffType, setSelectedStaffType] = useState(null);

  async function loadOverview() {
    setLoading(true);
    setLoadError("");

    try {
      const result = await getDashboardOverview();
      const schools = Array.isArray(result)
        ? result
        : result.schools;

      if (!Array.isArray(schools)) {
        throw new Error("The Moodle overview response has no schools array.");
      }

      setEntities(schools);
    } catch (error) {
      setLoadError(error.message || "Unable to load dashboard data.");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    loadOverview();
  }, []);

  function showOverview() {
    setSelectedEntity(null);
    setSelectedMetric(null);
    setSelectedUserCourses(null);
    setSelectedStaffType(null);
  }

  function showEntityDetails(entity) {
    setSelectedEntity(entity);
    setSelectedMetric(null);
    setSelectedUserCourses(null);
    setSelectedStaffType(null);
  }

  function showMetricDetails(entity, metric) {
    setSelectedEntity(entity);
    setSelectedUserCourses(null);

    if (metric.key === "visitingFaculty" || metric.key === "labInstructors") {
      setSelectedMetric(null);
      setSelectedStaffType(
        metric.key === "visitingFaculty" ? "visitingFaculty" : "labInstructor"
      );
      return;
    }

    setSelectedMetric(metric);
    setSelectedStaffType(null);
  }

  function showUserCourses(user, userType) {
    setSelectedUserCourses({ user, userType });
    setSelectedStaffType(null);
  }

  function showUniversityStaff(staffType) {
    setSelectedStaffType(staffType);
    setSelectedUserCourses(null);
  }

  function backToEntityDetails() {
    setSelectedMetric(null);
    setSelectedUserCourses(null);
    setSelectedStaffType(null);
  }

  if (loading) {
    return (
      <div className="university-dashboard" role="status">
        <p>Loading..</p>
      </div>
    );
  }

  if (loadError) {
    return (
      <main className="university-dashboard" role="alert">
        <h1>Unable to load dashboard</h1>
        <p>{loadError}</p>
        <button type="button" onClick={loadOverview}>
          Retry
        </button>
      </main>
    );
  }

  return (
    <div className="university-dashboard">
      {!selectedEntity ? (
        <OverviewPage entities={entities} onSelect={showEntityDetails} />
      ) : selectedUserCourses ? (
        <UserCoursesPage
          user={selectedUserCourses.user}
          userType={selectedUserCourses.userType}
          onBack={() => setSelectedUserCourses(null)}
        />
      ) : selectedStaffType ? (
        
<UniversityStaffPage
  staffType={selectedStaffType}
  entities={entities}
  onBack={() => setSelectedStaffType(null)}
/>

      ) : selectedMetric ? (
        <MetricDetailsPage
          key={`${selectedEntity.shortname}-${selectedMetric.key}`}
          entity={selectedEntity}
          metric={selectedMetric}
          onBack={backToEntityDetails}
          onUserCoursesSelect={showUserCourses}
          onUniversityStaffSelect={showUniversityStaff}
        />
      ) : (
        <EntityDetailsPage
          entity={selectedEntity}
          onBack={showOverview}
          onMetricSelect={showMetricDetails}
        />
      )}
    </div>
  );
}