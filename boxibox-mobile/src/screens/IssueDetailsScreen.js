import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import issueService from '../services/issueService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import StatusBadge from '../components/StatusBadge';
import { COLORS } from '../constants/colors';

const IssueDetailsScreen = ({ route, navigation }) => {
  const { issueId } = route.params;
  const [loading, setLoading] = useState(true);
  const [issue, setIssue] = useState(null);

  useEffect(() => {
    loadIssueDetails();
  }, []);

  const loadIssueDetails = async () => {
    try {
      const data = await issueService.getIssue(issueId);
      setIssue(data.issue || data);
    } catch (error) {
      console.error('Error loading issue details:', error);
      Alert.alert('Erreur', 'Impossible de charger les détails du signalement');
      navigation.goBack();
    } finally {
      setLoading(false);
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'urgent':
        return COLORS.error;
      case 'high':
        return COLORS.warning;
      case 'medium':
        return COLORS.info;
      case 'low':
        return COLORS.gray[500];
      default:
        return COLORS.gray[500];
    }
  };

  if (loading) {
    return <Loading />;
  }

  if (!issue) {
    return null;
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        <Card>
          <View style={styles.header}>
            <Text style={styles.issueNumber}>{issue.issue_number}</Text>
            <StatusBadge status={issue.status} label={issue.status_label} />
          </View>

          <Text style={styles.subject}>{issue.subject}</Text>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Type</Text>
            <Text style={styles.value}>{issue.type_label}</Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Priorité</Text>
            <Text style={[styles.value, { color: getPriorityColor(issue.priority) }]}>
              {issue.priority_label}
            </Text>
          </View>

          <View style={styles.infoRow}>
            <Text style={styles.label}>Créé le</Text>
            <Text style={styles.value}>
              {new Date(issue.created_at).toLocaleDateString('fr-FR')}
            </Text>
          </View>

          {issue.resolved_at && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Résolu le</Text>
              <Text style={styles.value}>
                {new Date(issue.resolved_at).toLocaleDateString('fr-FR')}
              </Text>
            </View>
          )}

          {issue.contract && (
            <View style={styles.infoRow}>
              <Text style={styles.label}>Contrat</Text>
              <Text style={styles.value}>{issue.contract.contract_number}</Text>
            </View>
          )}
        </Card>

        <Card>
          <Text style={styles.sectionTitle}>Description</Text>
          <Text style={styles.description}>{issue.description}</Text>
        </Card>

        {issue.resolution_notes && (
          <Card>
            <Text style={styles.sectionTitle}>Notes de résolution</Text>
            <Text style={styles.description}>{issue.resolution_notes}</Text>
          </Card>
        )}
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  issueNumber: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.text.primary,
  },
  subject: {
    fontSize: 20,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
  },
  label: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
    fontWeight: '600',
  },
  description: {
    fontSize: 14,
    color: COLORS.text.primary,
    lineHeight: 22,
  },
});

export default IssueDetailsScreen;
